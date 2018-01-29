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

use Neos\Flow\Http\HttpRequestHandlerInterface;

/**
 * Description of RequestHandler
 *
 * @author sven.kaffille@gmx.de
 */
class RequestHandler implements HttpRequestHandlerInterface{
    
    /**
     *
     * @var Bridge
     */
    protected $bridge;
    
    /**
     * 
     * @param Bridge $bridge
     */
    public function __construct(Bridge $bridge) {
        $this->bridge = $bridge;
    }
    
    public function canHandleRequest() {
        return true;
    }

    public function getHttpRequest() {
        return $this->bridge->getHttpRequest();
    }

    public function getHttpResponse() {
        return $this->bridge->getHttpResponse();
    }

    public function getPriority() {
        return 0;
    }

    public function handleRequest() {
        $this->bridge->handleRequest();
    }
}
