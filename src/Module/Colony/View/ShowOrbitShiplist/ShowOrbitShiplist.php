<?php

declare(strict_types=1);

namespace Stu\Module\Colony\View\ShowOrbitShiplist;

use Override;
use Stu\Component\Colony\OrbitShipListRetrieverInterface;
use Stu\Module\Colony\Lib\ColonyLoaderInterface;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Control\ViewControllerInterface;
use Stu\Module\Spacecraft\Lib\SpacecraftWrapperFactoryInterface;

final class ShowOrbitShiplist implements ViewControllerInterface
{
    public const string VIEW_IDENTIFIER = 'SHOW_ORBIT_SHIPLIST';

    public function __construct(
        private ColonyLoaderInterface $colonyLoader,
        private ShowOrbitShiplistRequestInterface $showOrbitShiplistRequest,
        private OrbitShipListRetrieverInterface $orbitShipListRetriever,
        private SpacecraftWrapperFactoryInterface $spacecraftWrapperFactory
    ) {}

    #[Override]
    public function handle(GameControllerInterface $game): void
    {
        $userId = $game->getUser()->getId();

        $colony = $this->colonyLoader->loadWithOwnerValidation(
            $this->showOrbitShiplistRequest->getColonyId(),
            $userId,
            false
        );

        $orbitShipList = [];

        foreach ($this->orbitShipListRetriever->retrieve($colony) as $array) {
            foreach ($array['ships'] as $spacecraft) {
                $orbitShipList[] = $this->spacecraftWrapperFactory->wrapSpacecraft($spacecraft);
            }
        }

        $game->setPageTitle(_('Schiffe im Orbit'));
        $game->setMacroInAjaxWindow('html/colony/orbitShipList.twig');
        $game->setTemplateVar('COLONY', $colony);
        $game->setTemplateVar(
            'WRAPPERS',
            $orbitShipList
        );
    }
}
