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
namespace Ppm\Adapter\Bridge;

use Neos\Flow\Http\Request as FlowRequest;
use Psr\Http\Message\ServerRequestInterface as PsrRequest;

class HeaderMapper
{
    /**
     * @var PsrRequest
     */
    protected $psrRequest;
    
    /**
     * @var string[]
     */
    protected $without;
    
    /**
     * 
     * @param PsrRequest $psrRequest
     * @param string[] $without headers not to map
     */
    public function __construct(PsrRequest $psrRequest, $without = [])
    {
        $this->psrRequest = $psrRequest;
        $this->without = $without;
    }
    
    /**
     * @param FlowRequest $flowRequest
     */
    public function execute(FlowRequest $flowRequest)
    {
        $headers = $this->psrRequest->getHeaders();
        foreach ($headers as $name => $value) {
            if (!in_array($name, $this->without)) {
                $flowRequest->setHeader($name, $value);
            }
        }
    }
}

