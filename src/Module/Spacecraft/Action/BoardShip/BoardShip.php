<?php

declare(strict_types=1);

namespace Stu\Module\Spacecraft\Action\BoardShip;

use Override;
use request;
use Stu\Component\Game\ModuleViewEnum;
use Stu\Component\Spacecraft\Nbs\NbsUtilityInterface;
use Stu\Component\Spacecraft\SpacecraftStateEnum;
use Stu\Exception\SanityCheckException;
use Stu\Module\Control\ActionControllerInterface;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Control\StuRandom;
use Stu\Module\Message\Lib\DistributedMessageSenderInterface;
use Stu\Module\Message\Lib\PrivateMessageFolderTypeEnum;
use Stu\Module\Prestige\Lib\CreatePrestigeLogInterface;
use Stu\Module\Spacecraft\Lib\Auxiliary\ShipShutdownInterface;
use Stu\Module\Spacecraft\Lib\Battle\FightLibInterface;
use Stu\Module\Spacecraft\Lib\CloseCombat\CloseCombatUtilInterface;
use Stu\Module\Spacecraft\Lib\Interaction\InteractionCheckerInterface;
use Stu\Module\Spacecraft\Lib\Interaction\ShipInteractionEnum;
use Stu\Module\Spacecraft\Lib\Interaction\ShipTakeoverManagerInterface;
use Stu\Module\Spacecraft\Lib\Interaction\ThreatReactionInterface;
use Stu\Module\Spacecraft\Lib\Message\MessageCollection;
use Stu\Module\Spacecraft\Lib\Message\MessageCollectionInterface;
use Stu\Module\Spacecraft\Lib\Message\MessageFactoryInterface;
use Stu\Module\Spacecraft\Lib\SpacecraftLoaderInterface;
use Stu\Module\Spacecraft\Lib\SpacecraftStateChangerInterface;
use Stu\Module\Spacecraft\View\ShowSpacecraft\ShowSpacecraft;
use Stu\Module\Spacecraft\Lib\SpacecraftWrapperInterface;
use Stu\Orm\Entity\CrewAssignmentInterface;
use Stu\Orm\Repository\CrewRepositoryInterface;
use Stu\Orm\Repository\CrewAssignmentRepositoryInterface;
use Stu\Orm\Repository\UserRepositoryInterface;

final class BoardShip implements ActionControllerInterface
{
    public const string ACTION_IDENTIFIER = 'B_BOARD_SHIP';

    /** @param SpacecraftLoaderInterface<SpacecraftWrapperInterface> $spacecraftLoader */
    public function __construct(
        private  CrewRepositoryInterface $crewRepository,
        private CrewAssignmentRepositoryInterface $shipCrewRepository,
        private UserRepositoryInterface $userRepository,
        private SpacecraftLoaderInterface $spacecraftLoader,
        private InteractionCheckerInterface $interactionChecker,
        private NbsUtilityInterface $nbsUtility,
        private CloseCombatUtilInterface $closeCombatUtil,
        private ThreatReactionInterface $threatReaction,
        private FightLibInterface $fightLib,
        private SpacecraftStateChangerInterface $spacecraftStateChanger,
        private ShipShutdownInterface $shipShutdown,
        private ShipTakeoverManagerInterface $shipTakeoverManager,
        private CreatePrestigeLogInterface $createPrestigeLog,
        private DistributedMessageSenderInterface $distributedMessageSender,
        private MessageFactoryInterface $messageFactory,
        private StuRandom $stuRandom
    ) {}

    #[Override]
    public function handle(GameControllerInterface $game): void
    {
        $game->setView(ShowSpacecraft::VIEW_IDENTIFIER);

        $user = $game->getUser();
        $userId = $user->getId();

        $shipId = request::getIntFatal('id');
        $targetId = request::getIntFatal('target');

        $wrappers = $this->spacecraftLoader->getWrappersBySourceAndUserAndTarget(
            $shipId,
            $userId,
            $targetId
        );

        $wrapper = $wrappers->getSource();
        $ship = $wrapper->get();

        $targetWrapper = $wrappers->getTarget();
        if ($targetWrapper === null) {
            return;
        }
        $target = $targetWrapper->get();

        if (!$target->isBoardingPossible()) {
            return;
        }

        if ($target->getUser() === $user) {
            return;
        }

        if ($target->getCrewCount() === 0) {
            return;
        }

        if ($targetWrapper->get()->getShieldState()) {
            return;
        }

        if ($target->getUser()->isNpc()) {
            $game->addInformation(_('Aktion nicht möglich, der Spieler ist NPC!'));
            return;
        }

        if ($target->getUser()->isVacationRequestOldEnough()) {
            $game->addInformation(_('Aktion nicht möglich, der Spieler befindet sich im Urlaubsmodus!'));
            return;
        }

        if (!$this->interactionChecker->checkPosition($target, $ship)) {
            throw new SanityCheckException('InteractionChecker->checkPosition failed', self::ACTION_IDENTIFIER);
        }

        if (!$this->fightLib->canAttackTarget($ship, $target, false, false)) {
            throw new SanityCheckException('Target cant be attacked', self::ACTION_IDENTIFIER);
        }

        if ($target->getCloakState() && !$this->nbsUtility->isTachyonActive($ship)) {
            throw new SanityCheckException('Attacked cloaked ship without active tachyon', self::ACTION_IDENTIFIER);
        }

        if ($ship->getCrewCount() === 0) {
            $game->addInformation(_('Aktion nicht möglich, keine Crew vorhanden!'));
            return;
        }

        $lastTakeover = $user->getLastBoarding();
        if (
            $lastTakeover !== null
            && time() < $lastTakeover +  ShipTakeoverManagerInterface::BOARDING_COOLDOWN_IN_SECONDS
        ) {
            $game->addInformation(sprintf(
                'Enterkommando kann erst wieder um %s entsendet werden',
                date('H:i', $lastTakeover +  ShipTakeoverManagerInterface::BOARDING_COOLDOWN_IN_SECONDS)
            ));
            return;
        }

        $epsSystemData = $wrapper->getEpsSystemData();
        if ($epsSystemData === null || $epsSystemData->getEps() === 0) {
            $game->addInformation(_('Keine Energie vorhanden'));
            return;
        }

        if ($ship->isDisabled()) {
            $game->addInformation(_('Das Schiff ist kampfunfähig'));
            return;
        }

        if ($this->fightLib->isTargetOutsideFinishedTholianWeb($ship, $target)) {
            $game->addInformation(_('Das Ziel ist nicht mit im Energienetz gefangen'));
            return;
        }

        if ($ship->getShieldState()) {
            $game->addInformation(_("Die Schilde sind aktiviert"));
            return;
        }

        $neededPrestige = $this->shipTakeoverManager->getPrestigeForBoardingAttempt($target);
        if ($user->getPrestige() < $neededPrestige) {
            $game->addInformation(sprintf(
                'Nicht genügend Prestige vorhanden, benötigt wird: %d',
                $neededPrestige
            ));
            return;
        }

        $targetName = $target->getName();
        $targetUserId = $target->getUser()->getId();

        $this->threatReaction->reactToThreat(
            $wrapper,
            $targetWrapper,
            ShipInteractionEnum::BOARD_SHIP
        );

        if ($ship->isDestroyed()) {
            $game->setView(ModuleViewEnum::SHIP);
            return;
        }

        if ($target->getShieldState()) {
            $game->addInformationf("Die %s hat die Schilde aktiviert. Enterkommando kann nicht entsendet werden.", $targetName);
            return;
        }

        $combatGroupAttacker = $this->closeCombatUtil->getCombatGroup($ship);
        $combatGroupDefender = $this->closeCombatUtil->getCombatGroup($target);

        $messages = new MessageCollection();
        $message = $this->messageFactory->createMessage($userId, $targetUserId, [sprintf(
            'Die %s entsendet ein Enterkommando auf die %s',
            $ship->getName(),
            $target->getName()
        )]);

        $messages->add($message);

        $this->spacecraftStateChanger->changeShipState($targetWrapper, SpacecraftStateEnum::SHIP_STATE_NONE);

        $this->createPrestigeLog->createLog(
            -$neededPrestige,
            sprintf(
                '-%d Prestige erhalten für einen Enterversuch auf die %s von Spieler %s',
                $neededPrestige,
                $target->getName(),
                $target->getUser()->getName()
            ),
            $user,
            time()
        );

        while (!empty($combatGroupAttacker) && !empty($combatGroupDefender)) {
            $this->cycleKillRound(
                $combatGroupAttacker,
                $combatGroupDefender,
                $wrapper,
                $targetWrapper,
                $messages
            );
        }

        $message = $this->messageFactory->createMessage($userId, $targetUserId);
        $messages->add($message);

        if (empty($combatGroupAttacker)) {
            $message->add('Der Enterversuch ist gescheitert');
        } elseif ($target->getCrewAssignments()->isEmpty()) {
            $message->add(sprintf(
                'Die Crew der %s wurde getötet. Übernahme kann nun erfolgen.',
                $target->getName()
            ));
        } else {
            $message->add(sprintf(
                'Es leben noch %d Crewman auf der %s.',
                $target->getCrewCount(),
                $target->getName()
            ));
        }

        $user->setLastBoarding(time());
        $this->userRepository->save($user);

        $this->sendPms(
            $userId,
            $ship->getSectorString(),
            $messages,
            $target->isStation()
        );

        $informations = $messages->getInformationDump();

        $game->addInformationWrapper($informations);
    }

    /**
     * @param array<CrewAssignmentInterface> $attackers
     * @param array<CrewAssignmentInterface> $defenders
     */
    private function cycleKillRound(
        array &$attackers,
        array &$defenders,
        SpacecraftWrapperInterface $wrapper,
        SpacecraftWrapperInterface $targetWrapper,
        MessageCollectionInterface $messages
    ): void {

        $ship = $wrapper->get();
        $target = $targetWrapper->get();

        $combatValueAttacker = $this->closeCombatUtil->getCombatValue($attackers, $ship->getUser()->getFaction());
        $combatValueDefender = $this->closeCombatUtil->getCombatValue($defenders, $target->getUser()->getFaction());

        $rand = $this->stuRandom->rand(
            0,
            $combatValueAttacker + $combatValueDefender
        );

        $isDeathOnDefenderSide = $rand <= $combatValueAttacker;
        if ($isDeathOnDefenderSide) {
            $killedCrewAssignment = $this->getKilledCrew($defenders, $targetWrapper);
        } else {
            $killedCrewAssignment = $this->getKilledCrew($attackers, $wrapper);
        }

        $message = $this->messageFactory->createMessage();
        $message->add(sprintf(
            '%s %s von der %s wurde im Kampf getötet',
            $killedCrewAssignment->getCrew()->getTypeDescription(),
            $killedCrewAssignment->getCrew()->getName(),
            $isDeathOnDefenderSide ? $target->getName() : $ship->getName()
        ));
        $messages->add($message);
    }

    /**
     * @param array<int, CrewAssignmentInterface> &$combatGroup
     */
    private function getKilledCrew(array &$combatGroup, SpacecraftWrapperInterface $wrapper): CrewAssignmentInterface
    {
        $keys = array_keys($combatGroup);
        shuffle($keys);

        $randomKey = current($keys);

        $killedCrewAssignment = $combatGroup[$randomKey];
        unset($combatGroup[$randomKey]);

        $ship = $wrapper->get();
        $ship->getCrewAssignments()->removeElement($killedCrewAssignment);
        $this->crewRepository->delete($killedCrewAssignment->getCrew());
        $this->shipCrewRepository->delete($killedCrewAssignment);

        if ($ship->getCrewAssignments()->isEmpty()) {
            $this->shipShutdown->shutdown($wrapper, true);
        }

        return $killedCrewAssignment;
    }

    private function sendPms(
        int $userId,
        string $sectorString,
        MessageCollectionInterface $messageCollection,
        bool $isTargetBase
    ): void {

        $header = sprintf(
            _("Enterversuch in Sektor %s"),
            $sectorString
        );

        $this->distributedMessageSender->distributeMessageCollection(
            $messageCollection,
            $userId,
            $isTargetBase ? PrivateMessageFolderTypeEnum::SPECIAL_STATION : PrivateMessageFolderTypeEnum::SPECIAL_SHIP,
            $header
        );
    }

    #[Override]
    public function performSessionCheck(): bool
    {
        return true;
    }
}
