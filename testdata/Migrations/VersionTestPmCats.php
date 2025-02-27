<?php

declare(strict_types=1);

namespace Stu\Testdata;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class VersionTestPmCats extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds default stu_pm_cats.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('INSERT INTO stu_pm_cats (id, user_id, description, sort, special, deleted)
                VALUES (46, 10, \'Schiffe\', 2, 2, NULL),
                       (47, 10, \'Handel\', 4, 4, NULL),
                       (58, 1, \'Schiffe\', 2, 2, NULL),
                       (74, 11, \'Schiffe\', 2, 2, NULL),
                       (75, 11, \'Handel\', 4, 4, NULL),
                       (81, 12, \'Schiffe\', 2, 2, NULL),
                       (82, 12, \'Handel\', 4, 4, NULL),
                       (83, 10, \'Persönlich\', 2, 1, NULL),
                       (84, 10, \'Kolonien\', 3, 3, NULL),
                       (90, 12, \'Schiffe\', 2, 3, NULL),
                       (4778, 101, \'Persönlich\', 1, 1, NULL),
                       (4779, 101, \'Schiffe\', 2, 2, NULL),
                       (4780, 101, \'Kolonien\', 3, 3, NULL),
                       (4781, 101, \'Handel\', 4, 4, NULL),
                       (4782, 101, \'Systemmeldungen\', 5, 5, NULL),
                       (4783, 101, \'Postausgang\', 6, 6, NULL),
                       (4784, 101, \'Stationen\', 7, 7, NULL),
                       (3707, 13, \'127\', 13, 0, NULL),
                       (3753, 13, \'145\', 18, 0, NULL),
                       (4324, 11, \'GL - Kommunikation\', 9, 0, NULL),
                       (3736, 10, \'Erledigt\', 10, 0, 1716476607),
                       (4348, 11, \'CVB - Kommunikation\', 11, 0, NULL),
                       (3696, 13, \'10\', 8, 0, NULL),
                       (86, 11, \'Persönlich\', 2, 1, NULL),
                       (89, 12, \'Posteingang\', 4, 1, NULL),
                       (85, 10, \'Postausgang\', 2, 6, NULL),
                       (327, 15, \'Handel\', 4, 4, NULL),
                       (328, 15, \'Persönlich\', 2, 1, NULL),
                       (329, 15, \'Kolonien\', 3, 3, NULL),
                       (331, 15, \'Schiffe\', 2, 2, NULL),
                       (4785, 102, \'Persönlich\', 1, 1, NULL),
                       (4786, 102, \'Schiffe\', 2, 2, NULL),
                       (4787, 102, \'Kolonien\', 3, 3, NULL),
                       (4788, 102, \'Handel\', 4, 4, NULL),
                       (4789, 102, \'Systemmeldungen\', 5, 5, NULL),
                       (4790, 102, \'Postausgang\', 6, 6, NULL),
                       (4791, 102, \'Stationen\', 7, 7, NULL),
                       (57, 1, \'Persönlich\', 2, 1, NULL),
                       (59, 1, \'Kolonien\', 3, 3, NULL),
                       (60, 1, \'Handel\', 4, 4, NULL),
                       (87, 11, \'Kolonien\', 4, 3, NULL),
                       (4760, 10, \'Senatorin Neireh (111)\', 15, 0, NULL),
                       (4441, 13, \'202\', 25, 0, NULL),
                       (921, 11, \'ROM - Taev\', 1, 0, 1677737778),
                       (952, 11, \'ROM - MisterX\', 1, 0, 1677737790),
                       (2870, 19, \'Postausgang\', 6, 6, NULL),
                       (2869, 19, \'Systemmeldungen\', 5, 5, NULL),
                       (1346, 11, \'IKA & CO\', 1, 0, 1680439713),
                       (1080, 10, \'Systemmeldungen\', 5, 5, NULL),
                       (2868, 19, \'Handel\', 4, 4, NULL),
                       (2867, 19, \'Kolonien\', 3, 3, NULL),
                       (2866, 19, \'Stationen\', 7, 7, NULL),
                       (2865, 19, \'Schiffe\', 2, 2, NULL),
                       (2864, 19, \'Persönlich\', 1, 1, NULL),
                       (1084, 15, \'Systemmeldungen\', 5, 5, NULL),
                       (88, 11, \'Postausgang\', 4, 6, NULL),
                       (91, 12, \'Postausgang\', 2, 6, NULL),
                       (330, 15, \'Postausgang\', 2, 6, NULL),
                       (61, 1, \'Postausgang\', 2, 6, NULL),
                       (4042, 13, \'Kampfhandlungen\', 1, 0, NULL),
                       (1003, 2, \'Systemmeldungen\', 5, 5, NULL),
                       (1005, 13, \'Systemmeldungen\', 5, 5, NULL),
                       (1009, 3, \'Systemmeldungen\', 5, 5, NULL),
                       (1010, 1, \'Systemmeldungen\', 5, 5, NULL),
                       (326, 14, \'Schiffe\', 0, 2, NULL),
                       (323, 14, \'Persönlich\', 1, 1, NULL),
                       (1161, 14, \'Stationen\', 2, 7, NULL),
                       (325, 14, \'Postausgang\', 3, 6, NULL),
                       (324, 14, \'Kolonien\', 4, 3, NULL),
                       (322, 14, \'Handel\', 5, 4, NULL),
                       (1031, 12, \'Systemmeldungen\', 5, 5, NULL),
                       (1047, 11, \'Systemmeldungen\', 5, 5, NULL),
                       (1053, 14, \'Systemmeldungen\', 6, 5, NULL),
                       (4761, 10, \'Takio Industries [ISA] (135)\', 16, 0, NULL),
                       (1154, 1, \'Stationen\', 6, 7, NULL),
                       (1155, 2, \'Stationen\', 6, 7, NULL),
                       (1156, 3, \'Stationen\', 6, 7, NULL),
                       (1157, 10, \'Stationen\', 6, 7, NULL),
                       (1158, 11, \'Stationen\', 6, 7, NULL),
                       (1159, 12, \'Stationen\', 6, 7, NULL),
                       (1162, 15, \'Stationen\', 6, 7, NULL),
                       (1555, 13, \'Kampfhandlungen\', 1, 0, 1688469297),
                       (318, 13, \'Persönlich\', 2, 1, NULL),
                       (319, 13, \'Kolonien\', 3, 3, NULL),
                       (317, 13, \'Handel\', 4, 4, NULL),
                       (1160, 13, \'Stationen\', 6, 7, NULL),
                       (320, 13, \'Postausgang\', 7, 6, NULL),
                       (4320, 10, \'GL - Gespräche\', 11, 0, NULL),
                       (3704, 10, \'Projekt Ladrillero - fertig\', 7, 0, 1716476614),
                       (4762, 10, \'{TB} Wasudharr-Republik (314)\', 17, 0, NULL),
                       (4749, 13, \'120\', 12, 0, NULL),
                       (3737, 13, \'135\', 14, 0, NULL),
                       (321, 13, \'Schiffe\', 0, 2, NULL),
                       (3688, 13, \'143\', 17, 0, NULL),
                       (4321, 10, \'DRK - Gespräche\', 12, 0, NULL),
                       (3705, 10, \'NPC - Gespräche\', 8, 0, NULL),
                       (4339, 11, \'LAT - Kommunikation\', 10, 0, NULL),
                       (4107, 17, \'Postausgang\', 6, 6, NULL),
                       (4106, 17, \'Systemmeldungen\', 5, 5, NULL),
                       (4105, 17, \'Handel\', 4, 4, NULL),
                       (4104, 17, \'Kolonien\', 3, 3, NULL),
                       (4103, 17, \'Stationen\', 7, 7, NULL),
                       (4102, 17, \'Schiffe\', 2, 2, NULL),
                       (4101, 17, \'Persönlich\', 1, 1, NULL),
                       (4322, 11, \'Ablage - Alt\', 7, 0, NULL),
                       (3706, 10, \'Projekt Ladrillero - unvollständig\', 9, 0, 1716476619),
                       (2247, 11, \'RC\', 1, 0, 1675696413),
                       (2250, 11, \'Plot-IU\', 1, 0, 1670598961),
                       (4251, 13, \'105\', 10, 0, NULL),
                       (3752, 13, \'138\', 15, 0, NULL),
                       (3672, 13, \'142\', 16, 0, NULL),
                       (4384, 13, \'169\', 21, 0, NULL),
                       (4401, 13, \'178\', 22, 0, NULL),
                       (2501, 11, \'GP\', 1, 0, 1677737726),
                       (1410, 11, \'cyan\', 1, 0, 1677737771),
                       (2248, 11, \'Plot-Tribble\', 1, 0, 1680439707),
                       (2251, 11, \'Plot-Konstrukt\', 1, 0, 1680439711),
                       (4262, 13, \'102\', 9, 0, NULL),
                       (4323, 11, \'ORG - Kommunikation\', 8, 0, NULL),
                       (4493, 13, \'149\', 19, 0, NULL),
                       (3992, 13, \'159\', 20, 0, NULL),
                       (4494, 13, \'183\', 23, 0, NULL),
                       (3768, 13, \'215\', 26, 0, NULL),
                       (3867, 13, \'216\', 27, 0, NULL),
                       (4250, 13, \'224\', 29, 0, NULL),
                       (4758, 10, \'[T³] - Suraza Triade (119)\', 13, 0, NULL),
                       (4777, 11, \'NPC - Föd\', 14, 0, NULL),
                       (4470, 11, \'BAM - Kommunikation\', 12, 0, NULL),
                       (4497, 11, \'ENA - Kommunikation\', 13, 0, NULL),
                       (4665, 13, \'111\', 11, 0, NULL),
                       (4759, 10, \'Autarch Wulfgar [ISA] (183)\', 14, 0, NULL),
                       (4610, 13, \'194\', 24, 0, NULL),
                       (4541, 13, \'222\', 28, 0, NULL);
        ');
    }
}
