<?php

declare(strict_types=1);

namespace Stu\Module\PlayerSetting;

use Stu\Module\Control\GameController;
use Stu\Module\Game\View\Overview\Overview;
use Stu\Module\PlayerSetting\Action\ActivateVacation\ActivateVacation;
use Stu\Module\PlayerSetting\Action\ChangeAvatar\ChangeAvatar;
use Stu\Module\PlayerSetting\Action\ChangeDescription\ChangeDescription;
use Stu\Module\PlayerSetting\Action\ChangeDescription\ChangeDescriptionRequest;
use Stu\Module\PlayerSetting\Action\ChangeDescription\ChangeDescriptionRequestInterface;
use Stu\Module\PlayerSetting\Action\ChangeEmail\ChangeEmail;
use Stu\Module\PlayerSetting\Action\ChangeEmail\ChangeEmailRequest;
use Stu\Module\PlayerSetting\Action\ChangeEmail\ChangeEmailRequestInterface;
use Stu\Module\PlayerSetting\Action\ChangePassword\ChangePassword;
use Stu\Module\PlayerSetting\Action\ChangePassword\ChangePasswordRequest;
use Stu\Module\PlayerSetting\Action\ChangePassword\ChangePasswordRequestInterface;
use Stu\Module\PlayerSetting\Action\ChangeRgbCode\ChangeRgbCode;
use Stu\Module\PlayerSetting\Action\ChangeSettings\ChangeSettings;
use Stu\Module\PlayerSetting\Action\ChangeUserName\ChangeUserName;
use Stu\Module\PlayerSetting\Action\ChangeUserName\ChangeUserNameRequest;
use Stu\Module\PlayerSetting\Action\ChangeUserName\ChangeUserNameRequestInterface;
use Stu\Module\PlayerSetting\Action\CreateTutorials\CreateTutorials;
use Stu\Module\PlayerSetting\Action\DeleteAccount\DeleteAccount;
use Stu\Module\PlayerSetting\Action\DeleteTutorials\DeleteTutorials;
use Stu\Module\PlayerSetting\Lib\ChangeUserSetting;
use Stu\Module\PlayerSetting\Lib\ChangeUserSettingInterface;

use function DI\autowire;

return [
    ChangeUserNameRequestInterface::class => autowire(ChangeUserNameRequest::class),
    ChangePasswordRequestInterface::class => autowire(ChangePasswordRequest::class),
    ChangeEmailRequestInterface::class => autowire(ChangeEmailRequest::class),
    ChangeDescriptionRequestInterface::class => autowire(ChangeDescriptionRequest::class),
    ChangeUserSettingInterface::class => autowire(ChangeUserSetting::class),
    'OPTIONS_ACTIONS' => [
        ChangeUserName::ACTION_IDENTIFIER => autowire(ChangeUserName::class),
        ChangePassword::ACTION_IDENTIFIER => autowire(ChangePassword::class),
        ChangeEmail::ACTION_IDENTIFIER => autowire(ChangeEmail::class),
        ChangeRgbCode::ACTION_IDENTIFIER => autowire(ChangeRgbCode::class),
        ChangeAvatar::ACTION_IDENTIFIER => autowire(ChangeAvatar::class),
        ChangeDescription::ACTION_IDENTIFIER => autowire(ChangeDescription::class),
        ChangeSettings::ACTION_IDENTIFIER => autowire(ChangeSettings::class),
        ActivateVacation::ACTION_IDENTIFIER => autowire(ActivateVacation::class),
        CreateTutorials::ACTION_IDENTIFIER => autowire(CreateTutorials::class),
        DeleteAccount::ACTION_IDENTIFIER => autowire(DeleteAccount::class),
        DeleteTutorials::ACTION_IDENTIFIER => autowire(DeleteTutorials::class),
    ],
    'OPTIONS_VIEWS' => [
        GameController::DEFAULT_VIEW => autowire(Overview::class),
    ]
];
