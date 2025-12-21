<?php
namespace TurboLabIt\ServiceEntityPlusBundle\Exception;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


class ItemNotFoundException extends NotFoundHttpException
{
    const string ITEM_NAME = '';


    public function __construct(string $identifier = '', ?\Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct($this->messageBuilder($identifier), $previous, $code, $headers);
    }


    protected function messageBuilder(string $identifier) : string
    {
        if( empty(static::ITEM_NAME) ) {

            $className  = (new \ReflectionClass($this))->getShortName();
            $itemName   = strstr($className, 'NotFoundException', true);

        } else {

            $itemName = static::ITEM_NAME;
        }

        return "$itemName ##$identifier## not found!";
    }
}
