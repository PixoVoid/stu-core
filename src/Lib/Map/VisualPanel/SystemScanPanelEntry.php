<?php

declare(strict_types=1);

namespace Stu\Lib\Map\VisualPanel;

use Stu\Orm\Entity\StarSystemInterface;

class SystemScanPanelEntry extends SignaturePanelEntry
{
    private StarSystemInterface $system;

    public function __construct(
        VisualPanelEntryData $data,
        StarSystemInterface $system
    ) {
        parent::__construct($data, null, null);

        $this->system = $system;
    }

    public function isClickAble(): bool
    {
        return true;
    }

    public function getOnClick(): string
    {
        return sprintf(
            'showSectorScanWindow(this, %d, %d, %d, %s);',
            $this->data->getPosX(),
            $this->data->getPosY(),
            $this->system->getId(),
            'false'
        );
    }
}