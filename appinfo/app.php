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

namespace OCA\UserOidc\AppInfo;

use OCP\AppFramework\App;
use OC_App;

require_once __DIR__ . '/autoload.php';

$app = new App('useroidc');
$container = $app->getContainer();

$container->query('OCP\INavigationManager')->add(function () use ($container) {
	$urlGenerator = $container->query('OCP\IURLGenerator');
	$l10n = $container->query('OCP\IL10N');
	return [
		// the string under which your app will be referenced in owncloud
		'id' => 'useroidc',

		// sorting weight for the navigation. The higher the number, the higher
		// will it be listed in the navigation
		'order' => 10,

		// the route that will be shown on startup
		'href' => $urlGenerator->linkToRoute('useroidc.auth.status'),

		// the icon that will be shown in the navigation
		// this file needs to exist in img/
		'icon' => $urlGenerator->imagePath('useroidc', 'app.svg'),

		// the title of your application. This will be used in the
		// navigation or on the settings page of your app
		'name' => $l10n->t('User Oidc'),
	];
});

$urlGenerator = $container->query('OCP\IURLGenerator');
$config = $container->query('ServerContainer')->getConfig();
foreach ($config->getSystemValue('openid_connect') as $id => $data) {
    OC_APP::registerLogIn(array(
        'href' => $urlGenerator->linkToRoute('useroidc.auth.login', ['provider' => $id]),
        'name' => $data['displayName'],
    ));
}
