<?php
namespace TurboLabIt\ServiceEntityPlusBundle\Trait;


trait CacheableDataTrait
{
    protected array $arrCachedData = [];


    public function setCachedData(string $cacheKey, mixed $data) : static
    {
        $this->arrCachedData[$cacheKey] = $data;
        return $this;
    }


    public function isCachedData(string $cacheKey) : bool { return array_key_exists($cacheKey, $this->arrCachedData); }


    public function getCachedData(string $cacheKey) : mixed
    {
        return $this->isCachedData($cacheKey) ? $this->arrCachedData[$cacheKey] : null;
    }
}
