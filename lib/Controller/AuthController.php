<?php
/**
 * ownCloud - useroidc
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Sigmund Augdal <sigmund.augdal@uninett.no>
 * @copyright Sigmund Augdal 2016
 */

namespace OCA\UserOidc\Controller;

use OCP\IRequest;
use OCP\IConfig;
use OCP\ILogger;
use OCP\ISession;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;
use \OCP\IURLGenerator;
use \OCP\IUserManager;
use \OCP\IUserSession;
use \OCP\Security\ISecureRandom;
use OCP\AppFramework\Http\RedirectResponse;
use OCA\UserOidc\OpenIDConnectClient;

class AuthController extends Controller {


	private $userId;

	public function __construct($AppName, IRequest $request, IConfig $config, ILogger $logger, IURLGenerator $urlgenerator, IUserManager $usermanager, ISecureRandom $securerandom, IUserSession $usersession, ISession $session, OpenIDConnectClient $oidc){
		parent::__construct($AppName, $request);
        $this->config = $config;
        $this->log = $logger;
        $this->urlgenerator = $urlgenerator;
        $this->usermanager = $usermanager;
        $this->securerandom = $securerandom;
        $this->usersession = $usersession;
        $this->session = $session;
        $this->oidc = $oidc;
	}

    /**
     * @PublicPage
     * @NoCSRFRequired
     * @UseSession
     */
    public function login($provider) {
        $this->oidc->setProvider($provider);
        $oidc_config = $this->config->getSystemValue('openid_connect')[$provider];
        $this->oidc->addScope($oidc_config['scopes']);
        $redirectUrl = $this->urlgenerator->linkToRouteAbsolute('useroidc.auth.login', ['provider' => $provider]);
        $this->log->debug('Using redirectUrl ' . $redirectUrl, ['app' => $this->appName]);
        $this->oidc->setRedirectUrl($redirectUrl);
        $this->oidc->authenticate();

        $email = $this->oidc->requestUserInfo('email');
        $name = $this->oidc->requestUserInfo('name');
        $user_id = $provider . '__' . $this->oidc->requestUserInfo('sub');

        $user = $this->usermanager->get($user_id);
        if(!$user) {
            $user = $this->createUser($user_id, $name, $email);
        }
        if(!$user) {
            return new RedirectResponse('/');
        }
        $this->session['oidc_access_token'] = $this->oidc->getAccessToken();
        $this->doLogin($user, $user_id);
        return new RedirectResponse('/');

    }

    private function doLogin($user) {
        $this->usersession->getSession()->regenerateId();
        $this->usersession->createSessionToken($this->request, $user->getUID(), $user->getUID());
        if ($this->usersession->login($user->getUID(), $this->usersession->getSession()->getId())) {
            $this->log->debug('login successful', ['app' => $this->appName]);
            $this->usersession->createSessionToken($this->request, $user->getUID(), $user->getUID());
            if ($this->usersession->isLoggedIn()) {
            }
        }
                
    }

    private function createUser($uid, $name, $email) {
        if (preg_match( '/[^a-zA-Z0-9 _\.@\-]/', $uid)) {
            $this->log->debug('Invalid username "'.$uid.'", allowed chars "a-zA-Z0-9" and "_.@-" ', ['app' => $this->appName]);
            return false;
        } else {
            $random_password = $this->securerandom->generate(64);
            $this->log->debug('Creating new user: '.$uid, ['app' => $this->appName]);
            $user = $this->usermanager->createUser($uid, $random_password);
            $user->setEMailAddress($email);
            $user->setDisplayName($name);
            return $user;
        }
    }


}