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
use PHPPM\Bootstraps\ApplicationEnvironmentAwareInterface;
use PHPPM\Bridges\BridgeInterface;
use Psr\Http\Message\ServerRequestInterface as PsrRequest;

/**
 * Description of Bridge
 *
 * @author sven.kaffille@gmx.de
 */
class Bridge implements BridgeInterface
{
    /**
     * @var FlowBootstrap
     */
    protected $application;
    
    /**
     * @var Bootstrap
     */
    protected $bootstrap;
    
    /**
     * @var Request
     */
    protected $request;
    
    /**
     * @var Response
     */
    protected $response;
    
    /**
     * @var ComponentChain
     */
    protected $baseComponentChain;
    
    /**
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
     * @var null|Bridge\FileMapper
     */
    protected $fileMapper;


    /**
     * Bootstrap an application implementing the HttpKernelInterface.
     * 
     * Is only called once per process.
     *
     * @param string|null $appBootstrap
     * The environment your application will use to bootstrap (if any)
     * @param string $appenv
     * @param boolean $debug If debug is enabled
     * @see http://stackphp.com
     */
    public function bootstrap($appBootstrap, $appenv, $debug)
    {
        $this->bootstrap = new $appBootstrap();
        if ($this->bootstrap instanceof ApplicationEnvironmentAwareInterface) {
            $this->bootstrap->initialize($appenv, $debug);
        }
        $this->application = $this->bootstrap->getApplication();
    }
    
    /**
     * Handle a request by converting it to a Flow request and routing it 
     * through Flow framework.
     * 
     * The resulting Flow response is returned. 
     * 
     * @param PsrRequest $request
     */
    public function handle(PsrRequest $request)
    {
        $this->mapRequest($request);
        $this->response = new Response();
        $this->application->registerRequestHandler(
            new RequestHandler($this)
        );
        $this->application->setPreselectedRequestHandlerClassName(
            RequestHandler::class
        );
        $this->application->run();
        $response = $this->mapResponse();
        $this->application->shutdown(FlowBootstrap::RUNLEVEL_RUNTIME);
        unset(
            $this->request,
            $this->response,
            $this->baseComponentChain,
            $this->componentContext
        );
        return $response;
    }

    /**
     * Handle the http request
     */
    public function handleRequest()
    {
        
        $this->bootFlow();
        
        if (isset($this->settings['http']['baseUri'])) {
            $this->request->setBaseUri(
                    new Uri($this->settings['http']['baseUri']
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
    public function getHttpRequest()
    {
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
        $this->componentContext = new ComponentContext(
            $this->request,
            $this->response
        );
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
     * @param PsrRequest $request
     */
    private function mapRequest(PsrRequest $request)
    {
        $method = $request->getMethod();
        $query = $request->getQueryParams();
        $post = $request->getParsedBody() ?: [];

        (new Bridge\CookieMapper($request, new Bridge\PhpSession()))->execute();

        $this->fileMapper = new Bridge\FileMapper($request);
        $uploadedFiles = $this->fileMapper->execute();
        
        $flowRequest = new Request($query, $post, $uploadedFiles, $_SERVER);
        $flowRequest->setMethod($method);
        (new Bridge\HeaderMapper($request))->execute($flowRequest);
        
        $this->request = $flowRequest;
    }
    
    /**
     * Cleanup before creating a new response instance
     * @return Response
     */
    private function mapResponse()
    {
        $this->fileMapper->cleanUp();
        $this->fileMapper = null;
        // create clone of original response to be PSR7 compliant
        return $this->response->withStatus($this->response->getStatusCode());
    }
}
