<?php
namespace App\Controllers;

use Monolog\Logger;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Router;
use Slim\Views\Twig;

class Controller{

	/**
	 * @var Logger
	 */
	protected $log;
	/**
	 * @var Router
	 */
	protected $router;

	public function __construct(Logger $log, Router $router)
	{
		$this->log = $log;
		$this->router = $router;
	}

	public function redirect(ResponseInterface $response, $location){
		return $response->withStatus(302)->withHeader('Location', $location);
	}

	public function pathFor($name, $params = []){
		return $this->router->pathFor($name, $params);
	}
}