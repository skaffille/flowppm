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
use Psr\Http\Message\UploadedFileInterface as PsrFile;
use Neos\Flow\Http\UploadedFile as FlowFile;

class FileMapper
{
    /**
     * @var PsrRequest
     */
    protected $psrRequest;
    
    /**
     * @var array|PsrFile[]
     */
    protected $files;
    
    /**
     * @var array|string[]
     */
    protected $tempFiles;
    
    /**
     * @param PsrRequest $psrRequest
     */
    public function __construct(PsrRequest $psrRequest)
    {
        $this->psrRequest = $psrRequest;
        $this->files = [];
        $this->tempFiles = [];
    }
    
    /**
     * @return array|PsrFile[]
     */
    public function execute()
    {
        /** @var PsrFile $file */
        $uploadedFiles = $this->psrRequest->getUploadedFiles();
        $this->mapFiles($uploadedFiles);
        return $this->files;
    }
    
    /**
     * Remove temporary files
     */
    public function cleanUp()
    {
        foreach ($this->tempFiles as $tmpname) {
            if (file_exists($tmpname)) {
                unlink($tmpname);
            }
        }
        unset(
            $this->psrRequest,
            $this->tempFiles,
            $this->files
        );
    }
    
    /**
     * @param array $files
     */
    protected function mapFiles($files)
    {
        foreach ($files as $value) {
            if (is_array($value)) {
                $this->mapFiles($value);
            } else if ($value instanceof PsrFile) {
                $tmpname = tempnam(sys_get_temp_dir(), 'upload');
                $this->tempFiles[] = $tmpname;
                file_put_contents($tmpname, (string)$value->getStream());
                $file = new FlowFile(
                    $tmpname,
                    $value->getSize(),
                    $value->getError(),
                    $value->getClientFilename(),
                    $value->getClientMediaType()
                );
                $this->files[] = $file;
            }
        }
    }
}