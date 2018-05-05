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

use Psr\Http\Message\ServerRequestInterface as PsrRequest;

class CookieMapper
{
    /**
     * @var PsrRequest
     */
    protected $psrRequest;
    
    /**
     * @var bool
     */
    protected $sessionCookieReceived;
    
    /**
     * @var PhpSession
     */
    protected $session;
    
    /**
     * @param PsrRequest $psrRequest
     */
    public function __construct(PsrRequest $psrRequest, PhpSession $session)
    {
        $_COOKIE = [];
        $this->psrRequest = $psrRequest;
        $this->session = $session;
        $this->sessionCookieReceived = false;
    }
    
    /**
     * 
     */
    public function execute()
    {
        $this->mapCookieHeadersToGlobal();
        $this->checkForSession();
    }
    
    /**
     * 
     */
    protected function mapCookieHeadersToGlobal()
    {
        foreach ($this->psrRequest->getHeader('Cookie') as $cookieHeader) {
            $cookies = explode(';', $cookieHeader);
            foreach ($cookies as $cookie) {
                list($name, $value) = explode('=', trim($cookie));
                $_COOKIE[$name] = $value;
            }
        }
    }
    
    protected function checkForSession()
    {
        foreach ($_COOKIE as $name => $value) {
            $this->setExistingSessionIfNecessary($name, $value);
            $this->createNewSessionIfNecessary();
        }
    }
    
    /**
     * @param string $name
     * @param string $value
     */
    protected function setExistingSessionIfNecessary($name, $value)
    {
        if ($name === $this->session->name()) {
            $this->session->id($value);
            $this->sessionCookieReceived = true;
        }
    }
    
    /**
     * 
     */
    protected function createNewSessionIfNecessary()
    {
        if (!$this->sessionCookieReceived 
            && $this->session->exists()
        ) {
            $this->session->generate();
        }
    }
}
