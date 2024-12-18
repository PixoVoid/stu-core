<?php

declare(strict_types=1);

namespace Stu\Module\Spacecraft\Lib;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Override;
use RuntimeException;
use Stu\Component\Spacecraft\Repair\RepairUtilInterface;
use Stu\Component\Spacecraft\SpacecraftAlertStateEnum;
use Stu\Component\Spacecraft\System\Data\AbstractSystemData;
use Stu\Component\Spacecraft\System\Data\EpsSystemData;
use Stu\Component\Spacecraft\System\Data\FusionCoreSystemData;
use Stu\Component\Spacecraft\System\Data\HullSystemData;
use Stu\Component\Spacecraft\System\Data\ProjectileLauncherSystemData;
use Stu\Component\Spacecraft\System\Data\ShieldSystemData;
use Stu\Component\Spacecraft\System\Data\SingularityCoreSystemData;
use Stu\Component\Spacecraft\System\Data\WarpCoreSystemData;
use Stu\Component\Spacecraft\System\Data\WarpDriveSystemData;
use Stu\Component\Spacecraft\System\Exception\SystemNotFoundException;
use Stu\Component\Spacecraft\System\SpacecraftSystemManagerInterface;
use Stu\Component\Spacecraft\System\SpacecraftSystemTypeEnum;
use Stu\Component\Spacecraft\System\SystemDataDeserializerInterface;
use Stu\Module\Commodity\CommodityTypeEnum;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Spacecraft\Lib\Interaction\ShipTakeoverManagerInterface;
use Stu\Module\Spacecraft\Lib\ReactorWrapper;
use Stu\Module\Spacecraft\Lib\ReactorWrapperInterface;
use Stu\Module\Spacecraft\Lib\ShipRepairCost;
use Stu\Module\Spacecraft\Lib\SpacecraftStateChangerInterface;
use Stu\Module\Spacecraft\Lib\SpacecraftWrapperFactoryInterface;
use Stu\Module\Ship\Lib\ShipWrapperInterface;
use Stu\Module\Spacecraft\Lib\Ui\StateIconAndTitle;
use Stu\Orm\Entity\SpacecraftSystemInterface;
use Stu\Orm\Entity\ShipTakeoverInterface;
use Stu\Orm\Entity\SpacecraftInterface;
use Stu\Orm\Repository\TorpedoTypeRepositoryInterface;

//TODO increase coverage
/**
 * @template T of SpacecraftInterface
 */
abstract class SpacecraftWrapper implements SpacecraftWrapperInterface
{
    /** @var Collection<int, AbstractSystemData> */
    private Collection $shipSystemDataCache;

    private ?ReactorWrapperInterface $reactorWrapper = null;

    private ?int $epsUsage = null;

    /**
     * @param T $spacecraft
     */
    public function __construct(
        protected SpacecraftInterface $spacecraft,
        private SpacecraftSystemManagerInterface $spacecraftSystemManager,
        private SystemDataDeserializerInterface $systemDataDeserializer,
        private TorpedoTypeRepositoryInterface $torpedoTypeRepository,
        protected GameControllerInterface $game,
        protected SpacecraftWrapperFactoryInterface $spacecraftWrapperFactory,
        private SpacecraftStateChangerInterface $spacecraftStateChanger,
        private RepairUtilInterface $repairUtil,
        private StateIconAndTitle $stateIconAndTitle
    ) {

        $this->shipSystemDataCache = new ArrayCollection();
    }

    #[Override]
    public function get(): SpacecraftInterface
    {
        return $this->spacecraft;
    }

    #[Override]
    public function getSpacecraftWrapperFactory(): SpacecraftWrapperFactoryInterface
    {
        return $this->spacecraftWrapperFactory;
    }

    #[Override]
    public function getSpacecraftSystemManager(): SpacecraftSystemManagerInterface
    {
        return $this->spacecraftSystemManager;
    }

    #[Override]
    public function getEpsUsage(): int
    {
        if ($this->epsUsage === null) {
            $this->epsUsage = $this->reloadEpsUsage();
        }
        return $this->epsUsage;
    }

    #[Override]
    public function lowerEpsUsage(int $value): void
    {
        $this->epsUsage -= $value;
    }

    private function reloadEpsUsage(): int
    {
        $result = 0;

        foreach ($this->spacecraftSystemManager->getActiveSystems($this->spacecraft) as $shipSystem) {
            $result += $this->spacecraftSystemManager->getEnergyConsumption($shipSystem->getSystemType());
        }

        if ($this->spacecraft->getAlertState() == SpacecraftAlertStateEnum::ALERT_YELLOW) {
            $result += SpacecraftStateChangerInterface::ALERT_YELLOW_EPS_USAGE;
        }
        if ($this->spacecraft->getAlertState() == SpacecraftAlertStateEnum::ALERT_RED) {
            $result += SpacecraftStateChangerInterface::ALERT_RED_EPS_USAGE;
        }

        return $result;
    }

    public function getReactorUsage(): int
    {
        $reactor = $this->reactorWrapper;
        if ($reactor === null) {
            throw new RuntimeException('this should not happen');
        }

        return $this->getEpsUsage() + $reactor->getUsage();
    }

    #[Override]
    public function getReactorWrapper(): ?ReactorWrapperInterface
    {
        if ($this->reactorWrapper === null) {
            $ship = $this->spacecraft;
            $reactorSystemData = null;


            if ($ship->hasShipSystem(SpacecraftSystemTypeEnum::SYSTEM_WARPCORE)) {
                $reactorSystemData = $this->getSpecificShipSystem(
                    SpacecraftSystemTypeEnum::SYSTEM_WARPCORE,
                    WarpCoreSystemData::class
                );
            }
            if ($ship->hasShipSystem(SpacecraftSystemTypeEnum::SYSTEM_SINGULARITY_REACTOR)) {
                $reactorSystemData = $this->getSpecificShipSystem(
                    SpacecraftSystemTypeEnum::SYSTEM_SINGULARITY_REACTOR,
                    SingularityCoreSystemData::class
                );
            }
            if ($ship->hasShipSystem(SpacecraftSystemTypeEnum::SYSTEM_FUSION_REACTOR)) {
                $reactorSystemData = $this->getSpecificShipSystem(
                    SpacecraftSystemTypeEnum::SYSTEM_FUSION_REACTOR,
                    FusionCoreSystemData::class
                );
            }

            if ($reactorSystemData === null) {
                return null;
            }

            $this->reactorWrapper = new ReactorWrapper($this, $reactorSystemData);
        }

        return $this->reactorWrapper;
    }

    #[Override]
    public function setAlertState(SpacecraftAlertStateEnum $alertState): ?string
    {
        $msg = $this->spacecraftStateChanger->changeAlertState($this, $alertState);
        $this->epsUsage = $this->reloadEpsUsage();

        return $msg;
    }

    /**
     * highest damage first, then prio
     *
     * @return SpacecraftSystemInterface[]
     */
    #[Override]
    public function getDamagedSystems(): array
    {
        $damagedSystems = [];
        $prioArray = [];
        foreach ($this->spacecraft->getSystems() as $system) {
            if ($system->getStatus() < 100) {
                $damagedSystems[] = $system;
                $prioArray[$system->getSystemType()->value] = $this->spacecraftSystemManager->lookupSystem($system->getSystemType())->getPriority();
            }
        }

        // sort by damage and priority
        usort(
            $damagedSystems,
            function (SpacecraftSystemInterface $a, SpacecraftSystemInterface $b) use ($prioArray): int {
                if ($a->getStatus() === $b->getStatus()) {
                    return $prioArray[$b->getSystemType()->value] <=> $prioArray[$a->getSystemType()->value];
                }
                return ($a->getStatus() < $b->getStatus()) ? -1 : 1;
            }
        );

        return $damagedSystems;
    }

    #[Override]
    public function isOwnedByCurrentUser(): bool
    {
        return $this->game->getUser() === $this->spacecraft->getUser();
    }

    #[Override]
    public function canBeRepaired(): bool
    {
        if ($this->spacecraft->getAlertState() !== SpacecraftAlertStateEnum::ALERT_GREEN) {
            return false;
        }

        if ($this->spacecraft->getShieldState()) {
            return false;
        }

        if ($this->spacecraft->getCloakState()) {
            return false;
        }

        if ($this->getDamagedSystems() !== []) {
            return true;
        }

        return $this->spacecraft->getHull() < $this->spacecraft->getMaxHull();
    }

    #[Override]
    public function canFire(): bool
    {
        $ship = $this->spacecraft;
        if (!$ship->getNbs()) {
            return false;
        }
        if (!$ship->hasActiveWeapon()) {
            return false;
        }

        $epsSystem = $this->getEpsSystemData();
        return $epsSystem !== null && $epsSystem->getEps() !== 0;
    }

    #[Override]
    public function getRepairDuration(): int
    {
        return $this->repairUtil->getRepairDuration($this);
    }

    #[Override]
    public function getRepairDurationPreview(): int
    {
        return $this->repairUtil->getRepairDurationPreview($this);
    }

    #[Override]
    public function getRepairCosts(): array
    {
        $neededParts = $this->repairUtil->determineSpareParts($this, false);

        $neededSpareParts = $neededParts[CommodityTypeEnum::COMMODITY_SPARE_PART];
        $neededSystemComponents = $neededParts[CommodityTypeEnum::COMMODITY_SYSTEM_COMPONENT];

        return [
            new ShipRepairCost($neededSpareParts, CommodityTypeEnum::COMMODITY_SPARE_PART, CommodityTypeEnum::getDescription(CommodityTypeEnum::COMMODITY_SPARE_PART)),
            new ShipRepairCost($neededSystemComponents, CommodityTypeEnum::COMMODITY_SYSTEM_COMPONENT, CommodityTypeEnum::getDescription(CommodityTypeEnum::COMMODITY_SYSTEM_COMPONENT))
        ];
    }

    #[Override]
    public function getPossibleTorpedoTypes(): array
    {
        if ($this->spacecraft->hasShipSystem(SpacecraftSystemTypeEnum::SYSTEM_TORPEDO_STORAGE)) {
            return $this->torpedoTypeRepository->getAll();
        }

        return $this->torpedoTypeRepository->getByLevel($this->spacecraft->getRump()->getTorpedoLevel());
    }

    #[Override]
    public function getTractoredShipWrapper(): ?ShipWrapperInterface
    {
        $tractoredShip = $this->spacecraft->getTractoredShip();
        if ($tractoredShip === null) {
            return null;
        }

        return $this->spacecraftWrapperFactory->wrapShip($tractoredShip);
    }

    #[Override]
    public function getStateIconAndTitle(): ?array
    {
        return $this->stateIconAndTitle->getStateIconAndTitle($this);
    }

    #[Override]
    public function getTakeoverTicksLeft(?ShipTakeoverInterface $takeover = null): int
    {
        $takeover ??= $this->spacecraft->getTakeoverActive();
        if ($takeover === null) {
            throw new RuntimeException('should not call when active takeover is null');
        }

        $currentTurn = $this->game->getCurrentRound()->getTurn();

        return $takeover->getStartTurn() + ShipTakeoverManagerInterface::TURNS_TO_TAKEOVER - $currentTurn;
    }

    #[Override]
    public function getCrewStyle(): string
    {
        $ship = $this->spacecraft;
        $excessCrew = $ship->getExcessCrewCount();

        if ($excessCrew === 0) {
            return "";
        }

        return $excessCrew > 0 ? "color: green;" : "color: red;";
    }

    #[Override]
    public function getHullSystemData(): HullSystemData
    {
        $hullSystemData = $this->getSpecificShipSystem(
            SpacecraftSystemTypeEnum::SYSTEM_HULL,
            HullSystemData::class
        );

        if ($hullSystemData === null) {
            throw new SystemNotFoundException('no hull installed?');
        }

        return $hullSystemData;
    }

    #[Override]
    public function getShieldSystemData(): ?ShieldSystemData
    {
        return $this->getSpecificShipSystem(
            SpacecraftSystemTypeEnum::SYSTEM_SHIELDS,
            ShieldSystemData::class
        );
    }

    #[Override]
    public function getEpsSystemData(): ?EpsSystemData
    {
        return $this->getSpecificShipSystem(
            SpacecraftSystemTypeEnum::SYSTEM_EPS,
            EpsSystemData::class
        );
    }

    #[Override]
    public function getWarpDriveSystemData(): ?WarpDriveSystemData
    {
        return $this->getSpecificShipSystem(
            SpacecraftSystemTypeEnum::SYSTEM_WARPDRIVE,
            WarpDriveSystemData::class
        );
    }

    #[Override]
    public function getProjectileLauncherSystemData(): ?ProjectileLauncherSystemData
    {
        return $this->getSpecificShipSystem(
            SpacecraftSystemTypeEnum::SYSTEM_TORPEDO,
            ProjectileLauncherSystemData::class
        );
    }

    /**
     * @template T2
     * @param class-string<T2> $className
     *
     * @return T2|null
     */
    protected function getSpecificShipSystem(SpacecraftSystemTypeEnum $systemType, string $className)
    {
        return $this->systemDataDeserializer->getSpecificShipSystem(
            $this->spacecraft,
            $systemType,
            $className,
            $this->shipSystemDataCache,
            $this->spacecraftWrapperFactory
        );
    }
}
