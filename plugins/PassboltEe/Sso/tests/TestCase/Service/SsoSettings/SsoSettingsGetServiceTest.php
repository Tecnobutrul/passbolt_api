<?php
declare(strict_types=1);

/**
 * Passbolt ~ Open source password manager for teams
 * Copyright (c) Passbolt SA (https://www.passbolt.com)
 *
 * Licensed under GNU Affero General Public License version 3 of the or any later version.
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Passbolt SA (https://www.passbolt.com)
 * @license       https://opensource.org/licenses/AGPL-3.0 AGPL License
 * @link          https://www.passbolt.com Passbolt(tm)
 * @since         3.9.0
 */

namespace Passbolt\Sso\Test\TestCase\Service\SsoSettings;

use App\Test\Factory\UserFactory;
use App\Utility\UuidFactory;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Exception\InternalErrorException;
use Passbolt\Sso\Model\Dto\AbstractSsoSettingsDto;
use Passbolt\Sso\Model\Dto\SsoSettingsDefaultDto;
use Passbolt\Sso\Model\Dto\SsoSettingsDto;
use Passbolt\Sso\Model\Entity\SsoSetting;
use Passbolt\Sso\Model\Entity\SsoState;
use Passbolt\Sso\Service\SsoSettings\SsoSettingsGetService;
use Passbolt\Sso\Test\Factory\SsoSettingsFactory;
use Passbolt\Sso\Test\Factory\SsoStateFactory;
use Passbolt\Sso\Test\Lib\SsoTestCase;

class SsoSettingsGetServiceTest extends SsoTestCase
{
    public function testSsoSettingsGetService_getByIdOrFail_Success(): void
    {
        $ssoSetting = SsoSettingsFactory::make()->azure()->draft()->persist();

        /** @var SsoSettingsDto $ssoSetting */
        $ssoSettingsDto = (new SsoSettingsGetService())->getByIdOrFail($ssoSetting->id);

        $this->assertEquals($ssoSetting->id, $ssoSettingsDto->id);
        $this->assertEquals($ssoSetting->status, $ssoSettingsDto->status);
        $this->assertEquals(SsoSetting::PROVIDER_AZURE, $ssoSettingsDto->getProvider());
        $this->assertEquals(SsoSetting::ALLOWED_PROVIDERS, $ssoSettingsDto->getProviders());
        $this->assertEquals($ssoSetting->created_by, $ssoSettingsDto->created_by);
        $this->assertEquals($ssoSetting->modified_by, $ssoSettingsDto->modified_by);
        $this->assertNotEmpty($ssoSetting->created);
        $this->assertNotEmpty($ssoSetting->modified);
    }

    public function testSsoSettingsGetService_getByIdOrFail_Error(): void
    {
        $this->expectException(RecordNotFoundException::class);
        (new SsoSettingsGetService())->getByIdOrFail(UuidFactory::uuid());
    }

    public function testSsoSettingsGetService_getByIdOrFail_ErrorDecryption(): void
    {
        $armoredMessage = file_get_contents(FIXTURES . DS . 'OpenPGP' . DS . 'Messages' . DS . 'ada_for_betty_signed.msg');
        $ssoSetting = SsoSettingsFactory::make()
            ->data($armoredMessage)
            ->persist();
        $this->expectException(InternalErrorException::class);
        (new SsoSettingsGetService())->getByIdOrFail($ssoSetting->id);
    }

    public function testSsoSettingsGetService_getActiveOrDefault_Success(): void
    {
        $ssoSettingActive = SsoSettingsFactory::make()->azure()->active()->persist();
        $ssoSettingDraft = SsoSettingsFactory::make()->azure()->draft()->persist();

        $this->assertEquals(2, SsoSettingsFactory::count());

        /** @var AbstractSsoSettingsDto $ssoSetting */
        $ssoSetting = (new SsoSettingsGetService())->getActiveOrDefault();
        $this->assertEquals(SsoSetting::PROVIDER_AZURE, $ssoSetting->getProvider());
        $this->assertEquals(SsoSetting::ALLOWED_PROVIDERS, $ssoSetting->getProviders());

        $this->assertTrue($ssoSetting instanceof SsoSettingsDto);
        $this->assertEquals($ssoSettingActive->id, $ssoSetting->id);
        $this->assertEquals($ssoSettingActive->status, $ssoSetting->status);
        $this->assertEquals(SsoSetting::STATUS_ACTIVE, $ssoSetting->status);

        /**
         * @psalm-suppress RedundantCondition needed to avoid AbstractSsoSettingsDto is not used in this file sniff
         */
        $this->assertTrue($ssoSetting instanceof AbstractSsoSettingsDto);

        // No data
        $this->assertEquals(true, !isset($ssoSetting->data));
    }

    public function testSsoSettingsGetService_getActiveOrDefault_SuccessWitData(): void
    {
        SsoSettingsFactory::make()->azure()->active()->persist();

        /** @var AbstractSsoSettingsDto $ssoSetting */
        $ssoSetting = (new SsoSettingsGetService())->getActiveOrDefault(true);

        // Decrypted data
        $this->assertTrue($ssoSetting instanceof SsoSettingsDto);
        $this->assertEquals(true, isset($ssoSetting->data));
    }

    public function testSsoSettingsGetService_getActiveOrDefault_SuccessDefault(): void
    {
        $this->assertEquals(0, SsoSettingsFactory::count());

        /** @var AbstractSsoSettingsDto $ssoSetting */
        $ssoSetting = (new SsoSettingsGetService())->getActiveOrDefault();

        $this->assertTrue($ssoSetting instanceof SsoSettingsDefaultDto);
        $this->assertEquals(null, $ssoSetting->getProvider());
        $this->assertEquals(SsoSetting::ALLOWED_PROVIDERS, $ssoSetting->getProviders());
        $this->assertTrue(!isset($ssoSetting->status));
        $this->assertTrue(!isset($ssoSetting->created));
        $this->assertTrue(!isset($ssoSetting->modified));
        $this->assertTrue(!isset($ssoSetting->created_by));
        $this->assertTrue(!isset($ssoSetting->modified_by));
        $this->assertTrue(!isset($ssoSetting->data));
    }

    public function testSsoSettingsGetService_getActiveOrDefault_SuccessDefaultWithData(): void
    {
        $this->assertEquals(0, SsoSettingsFactory::count());
        $ssoSetting = (new SsoSettingsGetService())->getActiveOrDefault(true);
        $this->assertEquals(true, !isset($ssoSetting->data));
    }

    public function testSsoSettingsGetService_getActiveOrDefault_DefaultIfDecryptionFails(): void
    {
        $armoredMessage = file_get_contents(FIXTURES . DS . 'OpenPGP' . DS . 'Messages' . DS . 'ada_for_betty_signed.msg');
        SsoSettingsFactory::make()->data($armoredMessage)->active()->persist();
        $ssoSetting = (new SsoSettingsGetService())->getActiveOrDefault(true);
        $this->assertEquals(null, $ssoSetting->getProvider());
        $this->assertEquals(SsoSetting::ALLOWED_PROVIDERS, $ssoSetting->getProviders());
        $this->assertEquals(true, !isset($ssoSetting->data));
    }

    public function testSsoSettingsGetService_getDraftById_Success(): void
    {
        $ssoSetting = SsoSettingsFactory::make()->azure()->draft()->persist();
        $result = (new SsoSettingsGetService())->getDraftByIdOrFail($ssoSetting->id);
        $this->assertEquals(SsoSetting::STATUS_DRAFT, $result->status);
        $this->assertTrue(!isset($result->data));
    }

    public function testSsoSettingsGetService_getDraftById_Error(): void
    {
        $this->expectException(RecordNotFoundException::class);
        (new SsoSettingsGetService())->getDraftByIdOrFail(UuidFactory::uuid());
    }

    public function testSsoSettingsGetService_getDraftSettingFromTokenOrFail_Success(): void
    {
        $settings = SsoSettingsFactory::make()->azure()->draft()->persist();
        $user = UserFactory::make()->admin()->active()->persist();
        $ssoState = SsoStateFactory::make()
            ->withTypeSsoState()
            ->userId($user->id)
            ->ssoSettingsId($settings->id)
            ->persist();

        $result = (new SsoSettingsGetService())->getDraftSettingFromStateOrFail($ssoState->state);
        $this->assertEquals(SsoSetting::STATUS_DRAFT, $result->status);
        $this->assertTrue(isset($result->data));
    }

    public function testSsoSettingsGetService_getDraftSettingFromTokenOrFail_Error(): void
    {
        $this->expectException(RecordNotFoundException::class);

        (new SsoSettingsGetService())->getDraftSettingFromStateOrFail(SsoState::generate());
    }
}
