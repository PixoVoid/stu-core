<?php

namespace Stu\Module\Spacecraft\Lib\Battle;

use Stu\Lib\Information\InformationInterface;
use Stu\Module\Spacecraft\Lib\Battle\Party\AttackedBattleParty;
use Stu\Module\Spacecraft\Lib\Battle\Party\AttackingBattleParty;
use Stu\Module\Spacecraft\Lib\Battle\Party\BattlePartyFactoryInterface;
use Stu\Module\Ship\Lib\FleetWrapperInterface;
use Stu\Module\Spacecraft\Lib\ShipNfsItem;
use Stu\Module\Spacecraft\Lib\SpacecraftWrapperInterface;
use Stu\Orm\Entity\ShipInterface;
use Stu\Orm\Entity\SpacecraftInterface;

interface FightLibInterface
{
    public function ready(SpacecraftWrapperInterface $wrapper, InformationInterface $informations): void;

    public function canAttackTarget(
        SpacecraftInterface $spacecraft,
        SpacecraftInterface|ShipNfsItem $nfsItem,
        bool $checkCloaked = false,
        bool $checkActiveWeapons = true,
        bool $checkWarped = true
    ): bool;

    /**
     * @return array{0: AttackingBattleParty, 1: AttackedBattleParty, 2: bool}
     */
    public function getAttackersAndDefenders(
        SpacecraftWrapperInterface|FleetWrapperInterface $wrapper,
        SpacecraftWrapperInterface $target,
        BattlePartyFactoryInterface $battlePartyFactory
    ): array;

    public function isTargetOutsideFinishedTholianWeb(SpacecraftInterface $ship, SpacecraftInterface $target): bool;

    public function calculateHealthPercentage(ShipInterface $target): int;
}
