<?php

declare(strict_types=1);

namespace Imi\RoadRunner\Http\Message;

use Imi\Server\Http\Message\Response;
use Imi\Util\Http\Consts\MediaType;
use Imi\Util\Http\Consts\ResponseHeader;
use Imi\Util\Stream\FileStream;
use Imi\Util\Stream\StreamMode;

class RoadRunnerResponse extends Response
{
    protected ?\Spiral\RoadRunner\Http\PSR7Worker $worker = null;

    /**
     * 是否可写.
     */
    protected bool $isWritable = true;

    public function __construct(?\Spiral\RoadRunner\Http\PSR7Worker $worker = null)
    {
        parent::__construct();
        $this->worker = $worker;
    }

    /**
     * {@inheritDoc}
     */
    public function isHeaderWritable(): bool
    {
        return $this->isWritable;
    }

    /**
     * {@inheritDoc}
     */
    public function isBodyWritable(): bool
    {
        return $this->isWritable;
    }

    /**
     * {@inheritDoc}
     */
    public function send(): self
    {
        if ($this->isWritable)
        {
            $cookieParams = $this->getCookieParams();
            if ($cookieParams)
            {
                foreach ($cookieParams as $cookie)
                {
                    $this->addHeader(ResponseHeader::SET_COOKIE, $this->cookieArrayToHeader($cookie));
                }
            }
            $this->worker->respond($this);
            $this->isWritable = false;
        }

        return $this;
    }

    protected function cookieArrayToHeader(array $cookie): string
    {
        $header = rawurlencode($cookie['key']) . '=' . rawurlencode($cookie['value']);
        if ($cookie['expire'] > 0)
        {
            $header .= '; Expires=' . gmdate(\DateTime::COOKIE, $cookie['expire']);
        }
        if ('' !== $cookie['path'])
        {
            $header .= '; Path=' . $cookie['path'];
        }
        if ('' !== $cookie['domain'])
        {
            $header .= '; Domain=' . $cookie['domain'];
        }
        if ($cookie['secure'])
        {
            $header .= '; Secure';
        }
        if ($cookie['httponly'])
        {
            $header .= '; HttpOnly';
        }

        return $header;
    }

    /**
     * {@inheritDoc}
     */
    public function sendFile(string $filename, ?string $contentType = null, ?string $outputFileName = null, int $offset = 0, int $length = 0): self
    {
        if ($this->isWritable)
        {
            if (null === $outputFileName)
            {
                $outputFileName = basename($filename);
            }
            $this->setHeader('Content-Disposition', 'attachment; filename*=UTF-8\'\'' . rawurlencode($outputFileName));

            if (null === $contentType)
            {
                $outputFileNameExt = pathinfo($outputFileName, \PATHINFO_EXTENSION);
                $contentType = MediaType::getContentType($outputFileNameExt);
                if (MediaType::APPLICATION_OCTET_STREAM === $contentType)
                {
                    $fileNameExt = pathinfo($filename, \PATHINFO_EXTENSION);
                    if ($fileNameExt !== $outputFileNameExt)
                    {
                        $contentType = MediaType::getContentType($fileNameExt);
                    }
                }
            }
            $this->setHeader('Content-Type', $contentType);
            $this->setBody(new FileStream($filename, StreamMode::READONLY));

            $this->worker->respond($this);
            $this->isWritable = false;
        }

        return $this;
    }
}
