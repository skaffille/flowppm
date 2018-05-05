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

use PHPPM\Utils;

class PhpSession
{
    /**
     * 
     * @param string $name
     * @return string
     */
    public function name($name = null) 
    {
        return session_name($name);
    }
    
    /**
     * 
     * @param string $id
     * @return string
     */
    public function id($id = null)
    {
        return session_id($id);
    }
    
    /**
     * @return bool
     */
    public function exists()
    {
        return ($this->id() !== '');
    }

        /**
     * @return string
     */
    public function generate()
    {
        return $this->id(Utils::generateSessionId());
    }
}