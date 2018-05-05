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

use Neos\Flow\Core\Bootstrap as FlowBootstrap;
use PHPPM\Bootstraps\ApplicationEnvironmentAwareInterface;

/**
 * Description of Application
 *
 * @author sven.kaffille@gmx.de
 */
class Bootstrap implements ApplicationEnvironmentAwareInterface
{
    protected $appenv; 
    
    protected $debug;
    
    public function getApplication()
    {
        $context = $this->appenv ?: 'Development';
        $bootstrap = new FlowBootstrap('Production');

        return $bootstrap;
    }

    public function initialize($appenv, $debug)
    {
        if ($appenv === 'dev') {
            $appenv = 'Development';
        }
        if (!defined('FLOW_PATH_ROOT') || !defined('FLOW_PATH_WEB')) {
            
            $_SERVER['SCRIPT_FILENAME'] = 
                $this->getFlowWebDirPath() . 'index.php';
        }
        $this->appenv = $appenv;
        $this->debug = $debug;
    }
    
    /**
     * 
     * @return string
     */
    protected function getFlowWebDirPath()
    {
        $reflector = new \ReflectionClass(FlowBootstrap::class);
        return dirname($reflector->getFileName()) . '/../../../../../Web/';
    }
}
