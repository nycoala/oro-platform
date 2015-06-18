<?php

namespace Oro\Bundle\ImapBundle\Mail\Storage;

use \Zend\Mail\Header\ContentType;
use \Zend\Mail\Header\HeaderInterface;
use \Zend\Mail\Storage\Part;

class Message extends \Zend\Mail\Storage\Message
{
    /**
     * {@inheritdoc}
     */
    public function __construct(array $params)
    {
        parent::__construct($params);
    }

    /**
     * Gets the message attachments
     *
     * @return Body
     * @throws \Zend\Mail\Storage\Exception\RuntimeException
     */
    public function getBody()
    {
        return new Body($this);
    }

    /**
     * Gets the message attachments
     *
     * @return Attachment[]
     */
    public function getAttachments()
    {
        return $this->isMultipart()
            ? $this->getMultiPartAttachments($this)
            : [];
    }

    /**
     * @return null|ContentType
     */
    public function getPriorContentType()
    {
        if ($this->isMultipart()) {
            return $this->getMultiPartPriorContentType($this);
        } else {
            return $this->getPartContentType($this);
        }
    }

    /**
     * Gets the Content-Type for the given part
     *
     * @param Part $part The message part
     *
     * @return \Zend\Mail\Header\ContentType|null
     */
    protected function getPartContentType($part)
    {
        return $part->getHeaders()->has('Content-Type')
            ? $part->getHeader('Content-Type')
            : null;
    }

    /**
     * Gets the Content-Disposition for the given part
     *
     * @param Part $part   The message part
     * @param bool $format Can be FORMAT_RAW or FORMAT_ENCODED, see HeaderInterface::FORMAT_* constants
     *
     * @return string|null
     */
    protected function getPartContentDisposition($part, $format = HeaderInterface::FORMAT_RAW)
    {
        return $part->getHeaders()->has('Content-Disposition')
            ? $part->getHeader('Content-Disposition')->getFieldValue($format)
            : null;
    }

    /**
     * @param Part $multiPart
     * @return bool|null|ContentType
     */
    protected function getMultiPartPriorContentType(Part $multiPart)
    {
        $textContentType = false;
        foreach ($multiPart as $part) {
            $contentType = $part->isMultipart()
                ? $this->getMultiPartPriorContentType($part)
                : $this->getPartContentType($part);
            if ($contentType) {
                $type = strtolower($contentType->getType());
                if ($type === 'text/html') {
                    // html is preferred part
                    return $contentType;
                } elseif ($type === 'text/plain') {
                    $textContentType = $contentType;
                }
            }
        }
        if ($textContentType) {
            // in case when only text part presents
            return $textContentType;
        } else {
            return null;
        }
    }

    /**
     * @param Part $multiPart
     * @return array
     */
    protected function getMultiPartAttachments(Part $multiPart)
    {
        $result = [];
        foreach ($multiPart as $part) {
            if ($part->isMultipart()) {
                $result = array_merge($this->getMultiPartAttachments($part), $result);
            } else {
                $attachment = $this->getPartAttachment($part);
                if ($attachment !== null) {
                    $result[] = $attachment;
                }
            }
        }

        return $result;
    }

    /**
     * @param Part $part
     * @return null|Attachment
     */
    protected function getPartAttachment(Part $part)
    {
        $contentType = $this->getPartContentType($part);
        if ($contentType !== null) {
            $name               = $contentType->getParameter('name');
            $contentDisposition = $this->getPartContentDisposition($part);
            if ($name !== null || $contentDisposition !== null) {
                // The Content-Disposition may be missed, because it is introduced only in RFC 2183
                // In this case it is assumed that any part which has ";name="
                // in the Content-Type is an attachment
                // param name of Content-type also may be missed
                // then we will use Content-Disposition header to detect part as attachment
                return new Attachment($part);
            }
        }

        return null;
    }
}
