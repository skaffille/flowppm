<?php
/*
 * Copyright 2017-2018 Sven Kaffille (sven.kaffille@gmx.de)
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */
namespace Ppm\Adapter;

use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Core\Bootstrap as FlowBootstrap;
use Neos\Flow\Http\Component\ComponentChain;
use Neos\Flow\Http\Component\ComponentContext;
use Neos\Flow\Http\Request;
use Neos\Flow\Http\Response;
use Neos\Flow\Http\Uri;
use PHPPM\Bootstraps\BootstrapInterface;
use PHPPM\Bootstraps\ApplicationEnvironmentAwareInterface;
use PHPPM\Bridges\BridgeInterface;

/**
 * Description of Bridge
 *
 * @author sven.kaffille@gmx.de
 */
class Bridge implements BridgeInterface {
    
    /**
     *
     * @var FlowBootstrap
     */
    protected $application;
    
    /**
     *
     * @var Bootstrap
     */
    protected $bootstrap;
    
    /**
     *
     * @var Request
     */
    protected $request;
    
    /**
     *
     * @var Response
     */
    protected $response;
    
    /**
     *
     * @var ComponentChain
     */
    protected $baseComponentChain;
    
    /**
     *
     * @var ComponentContext
     */
    protected $componentContext;
    
    /**
     * The "http" settings
     *
     * @var array
     */
    protected $settings;


    /**
     * Bootstrap an application implementing the HttpKernelInterface.
     *
     * @param string|null $appBootstrap The environment your application will use to bootstrap (if any)
     * @param string $appenv
     * @param boolean $debug If debug is enabled
     * @param LoopInterface $loop
     * @see http://stackphp.com
     */
    public function bootstrap($appBootstrap, $appenv, $debug, \React\EventLoop\LoopInterface $loop)
    {
        $this->bootstrap = new $appBootstrap();
        if ($this->bootstrap instanceof ApplicationEnvironmentAwareInterface) {
            $this->bootstrap->initialize($appenv, $debug);
        }
        if ($this->bootstrap instanceof BootstrapInterface) {
            $this->application = $this->bootstrap->getApplication();
        }
    }

    /**
     * Returns the repository which is used as root for the static file serving.
     *
     * @return string
     */
    public function getStaticDirectory() 
    {
        return $this->bootstrap->getStaticDirectory();
    }

    /**
     * Handle a request by converting it to a Flow request and routing it 
     * through Flow framework.
     * 
     * The resulting Flow response is mapped to the provided React HttpResponse.
     *
     * @param \React\Http\Request $request
     * @param \PHPPM\React\HttpResponse $response
     */
    public function onRequest(
            \React\Http\Request $request,
            \PHPPM\React\HttpResponse $reactResponse
    ) {
        $this->application = $this->bootstrap->getApplication();
        $this->mapRequest($request);
        $this->response = new Response();
        $this->application->registerRequestHandler(new RequestHandler($this));
        $this->application->setPreselectedRequestHandlerClassName(RequestHandler::class);
        $this->application->run();
        $this->mapResponse($reactResponse);
        $this->application->shutdown(FlowBootstrap::RUNLEVEL_RUNTIME);
        unset(
            $this->application,
            $this->request,
            $this->response,
            $this->baseComponentChain,
            $this->componentContext
        );
    }

    /**
     * Handle the http request
     */
    public function handleRequest() {
        
        $this->bootFlow();
        
        if (isset($this->settings['http']['baseUri'])) {
            $this->request->setBaseUri(
                    new \Neos\Flow\Http\Uri($this->settings['http']['baseUri']
                )
            );
        }
        $this->baseComponentChain->handle($this->componentContext);
        $this->response = $this->baseComponentChain->getResponse();
    }

    /**
     * Get the Flow http-request.
     *
     * @return Request
     */
    public function getHttpRequest() {
        return $this->request;
    }

    /**
     * Get the Flow http-response created by this.
     * @return Response
     */
    public function getHttpResponse()
    {
        return $this->response;
    }
    
    /**
     * Boots up Flow to runtime
     *
     * @return void
     */
    protected function bootFlow()
    {
        $this->componentContext = new ComponentContext($this->request, $this->response);
        $sequence = $this->application->buildRuntimeSequence();
        $sequence->invoke($this->application);
        $this->resolveFlowDependencies();
    }

    /**
     * Resolves a few dependencies of this request handler which can't be resolved
     * automatically due to the early stage of the boot process this request handler
     * is invoked at.
     *
     * @return void
     */
    protected function resolveFlowDependencies()
    {
        $objectManager = $this->application->getObjectManager();
        $this->baseComponentChain = $objectManager->get(
            ComponentChain::class
        );

        $configurationManager = $objectManager->get(
            ConfigurationManager::class
        );
        $this->settings = $configurationManager->getConfiguration(
            ConfigurationManager::CONFIGURATION_TYPE_SETTINGS,
            'Neos.Flow'
        );
    }

    /**
     * Map react http-request to flow http-request
     * 
     * @param \React\Http\Request $request
     * @return Request The flow request
     */
    private function mapRequest(\React\Http\Request $request)
    {
        $url = $request->getUrl()->__toString();
        
        $server = [
            'REMOTE_ADDR' => $request->getRemoteAddress(),
            'REQUEST_METHOD' => $request->getMethod(),
            'SERVER_PROTOCOL' => 'HTTP/'.$request->getHttpVersion(),
        ];
        $server = array_merge($server, $request->getHeaders());
        $this->request = Request::create(
                new Uri($url), 
                strtoupper($request->getMethod()),
                array_merge($request->getPost(), $request->getQuery()),
                $request->getFiles(),
                $server
        );
        return $this->request;
    }
    
    /**
     * Map flow http-response to provided $response. 
     *
     * @param \PHPPM\React\HttpResponse $response
     * @return \PHPPM\React\HttpResponse
     */
    private function mapResponse(\PHPPM\React\HttpResponse $response)
    {
        $content = $this->response->getContent();
        $headers = $this->response->getHeaders()->getAll();
        
        if (!isset($headers['Content-Length'])) {
            $headers['Content-Length'] = strlen($content);
        }
        $response->writeHead($this->response->getStatusCode(), $headers);
        $response->end($content);
        return $response;
    }
}
