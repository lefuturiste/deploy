<?php

use Psr\Container\ContainerInterface;

return [
	'settings.displayErrorDetails' => function (ContainerInterface $container){
		return $container->get('config')['app_debug'];
	},
	'settings.debug' => function (ContainerInterface $container){
		return $container->get('config')['app_debug'];
	},

	\Monolog\Logger::class => function (ContainerInterface $container) {
		$config = $container->get('config');
		$log = new Monolog\Logger($config['app_name']);

		$log->pushHandler(new Monolog\Handler\StreamHandler($config['log']['path'], $config['log']['level']));

		if ($config['log']['discord']) {
			$log->pushHandler(new \DiscordHandler\DiscordHandler(
				$config['log']['discord_webhooks'],
				$config['app_name'],
				$config['env_name'],
				$config['log']['level']
			));
		}
		return $log;
	},

	\Symfony\Component\Translation\Translator::class => function () {
		// First param is the "default language" to use.
		$translator = new \Symfony\Component\Translation\Translator('fr_FR', new \Symfony\Component\Translation\MessageSelector());
		// Set a fallback language incase you don't have a translation in the default language
		$translator->setFallbackLocales(['fr_FR']);
		// Add a loader that will get the php files we are going to store our translations in
		$translator->addLoader('php', new \Symfony\Component\Translation\Loader\PhpFileLoader());

		// Add language files here
		$translator->addResource('php', '../App/lang/fr_FR.php', 'fr_FR');
		$translator->addResource('php', '../App/lang/en_EN.php', 'en_EN');

		return $translator;
	},
//
//	\Simplon\Mysql\Mysql::class => function($container) {
//		$pdo = new \Simplon\Mysql\PDOConnector(
//			$container->get('config')['mysql']['host'], // server
//			$container->get('config')['mysql']['user'],     // user
//			$container->get('config')['mysql']['password'],      // password
//			$container->get('config')['mysql']['database']   // database
//		);
//
//		$pdoConn = $pdo->connect('utf8', []); // charset, options
//
//		$dbConn = new \Simplon\Mysql\Mysql($pdoConn);
//
//		return $dbConn;
//	}
];