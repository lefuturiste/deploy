<?php

namespace App\Controllers;

use DI\Container;
use DiscordWebhooks\Client;
use DiscordWebhooks\Embed;
use phpseclib\Net\SSH2;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Yaml\Yaml;

class EventApiController extends Controller
{
	/**
	 * On new event
	 *
	 * @param $domain
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @param Container $container
	 * @return mixed
	 * @internal param $args
	 */
	public function postNew($domain, ServerRequestInterface $request, ResponseInterface $response, Container $container)
	{
		//verify if the domain exist
		$domains = Yaml::parse(file_get_contents('../domains.yml'));
		if (array_key_exists($domain, $domains)) {
			$domainData = $domains[$domain];
			$numberOfDeploys = 0;
			$sshResponses = [];

			//log body
			$body = $request->getParsedBody();
			//get type of event
			foreach ($domainData['events'] as $eventKey => $eventValue){
				//si l'envent renseigné est celui qui est envoyé et qu'il est autorisé
				if ($eventKey == $body['object_kind'] && $eventValue == true) {
					//envoyer l'ordre de deploy au serveur ssh
					$ssh = new SSH2($domainData['ssh']['host'], $domainData['ssh']['port']);
					if ($ssh->login($domainData['ssh']['username'], $domainData['ssh']['password'])) {

						$sshResponse = $ssh->exec($domainData['deploy_command']);

						array_push($sshResponses, $sshResponse);

						//send discord webhook
						$webhook = new Client($domainData['discord_webhook']);

						switch ($body['object_kind']){
							case 'tag_push':
								$embed = new Embed();
								$tag = str_replace('refs/tags/', '', $body['ref']);
								$embed->title("New tag push on {$body['project']['path_with_namespace']}");
								$embed->url($body['project']['web_url']);
								$embed->image($body['user_avatar']);
								$embed->description("{$body['user_name']} pushed tag {$tag} on {$body['project']['path_with_namespace']}");
								$embed->field('Target server', $domainData['ssh']['host']);
								$webhook->embed($embed)->send();
								break;

							case 'push':
								//embed commits
								$embed = new Embed();
								$embed->title("New(s) commit(s) pushed on {$body['project']['path_with_namespace']}");
								$embed->description("By **{$body['user_name']}**");
								$embed->url($body['project']['web_url']);
								$embed->image($body['user_avatar']);
								$embed->field('Target server', $domainData['ssh']['host']);
								$commits = new Embed();
								foreach ($body['commits'] AS $commit){
									$commits->field($commit['author']['name'], $commit['message']);
								}
								$webhook->embed($embed)->embed($commits)->send();
								break;
						}

						$numberOfDeploys++;
					}

				}
			}
			return $response->withJson([
				'success' => true,
				'message' => "{$numberOfDeploys} servers has been deploy",
				'ssh_responses' => $sshResponses
 			]);
		} else {
			return $response->withJson([
				'success' => false,
				'error' => 'Domain not found!'
			])->withStatus(404);
		}
	}
}
