<?php
/**
 * 2007-2018 PrestaShop.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2018 PrestaShop SA
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace PrestaShopBundle\Routing\Converter;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class RoutingCacheKeyGenerator implements CacheKeyGeneratorInterface
{
    /**
     * @var array
     */
    private $coreRoutingPaths;

    /**
     * @var array
     */
    private $activeModulesPaths;

    /**
     * @var string
     */
    private $environment;

    /**
     * RoutingCacheKeyGenerator constructor.
     *
     * @param array $coreRoutingPaths
     * @param array $activeModulesPaths
     * @param string $environment
     */
    public function __construct(
        array $coreRoutingPaths,
        array $activeModulesPaths,
        $environment = 'dev'
    ) {
        $this->coreRoutingPaths = $coreRoutingPaths;
        $this->activeModulesPaths = $activeModulesPaths;
        $this->environment = $environment;
    }

    /**
     * @return array
     */
    public function getLastModifications()
    {
        $routingFiles = [];

        if (count($this->coreRoutingPaths)) {
            $finder = new Finder();
            $finder->files()->in($this->coreRoutingPaths);
            $finder->name('/\.(yml|yaml)$/');
            /** @var SplFileInfo $yamlFile */
            foreach ($finder as $yamlFile) {
                $routingFiles[$yamlFile->getPathname()] = $yamlFile->getMTime();
            }
        }

        foreach ($this->activeModulesPaths as $modulePath) {
            $routingFile = $modulePath . '/config/routes.yml';
            if (file_exists($routingFile)) {
                $routingFiles[$routingFile] = filemtime($routingFile);
            }
            $routingFile = $modulePath . '/config/routes.yaml';
            if (file_exists($routingFile)) {
                $routingFiles[$routingFile] = filemtime($routingFile);
            }
        }

        arsort($routingFiles);

        return $routingFiles;
    }

    /**
     * @return array|null
     */
    public function getLatestModification()
    {
        $lastModifications = $this->getLastModifications();
        if (!count($lastModifications)) {
            return null;
        }

        $filePaths = array_keys($lastModifications);

        return [
            'file_path' => $filePaths[0],
            'modified_time' => $lastModifications[$filePaths[0]],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheKey()
    {
        $cacheKey = preg_replace('@\\\\@', '_', __NAMESPACE__);
        if ('prod' !== $this->environment) {
            $latestModification = $this->getLatestModification();
            if (null !== $latestModification) {
                $cacheKey .= '_' . $latestModification['modified_time'];
            }
        }

        return  $cacheKey;
    }
}
