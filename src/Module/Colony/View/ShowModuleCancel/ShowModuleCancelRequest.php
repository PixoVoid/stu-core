<?php

declare(strict_types=1);

namespace Stu\Module\Colony\View\ShowModuleCancel;

use Override;
use Stu\Lib\Request\CustomControllerHelperTrait;

final class ShowModuleCancelRequest implements ShowModuleCancelRequestInterface
{
    use CustomControllerHelperTrait;

    #[Override]
    public function getColonyId(): int
    {
        return $this->parameter('id')->int()->required();
    }

    #[Override]
    public function getModuleId(): int
    {
        return $this->parameter('module')->int()->required();
    }
}
