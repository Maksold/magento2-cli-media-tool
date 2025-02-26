<?php
/**
 * @package     MagentoCode\CliMediaTool
 * @author      Brian Neff <bneff84@gmail.com>
 * @license     MIT
 */

namespace MagentoCode\CliMediaTool\Command;

use Magento\Catalog\Model\Product\Media\Config as ProductMediaConfig;
use Magento\Catalog\Model\ResourceModel\Product\Gallery;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;
use Symfony\Component\Console\Command\Command;

abstract class CatalogAbstract extends Command
{
    /**
     * Input key for removing unused images
     */
    const INPUT_KEY_REMOVE_UNUSED = 'remove_unused';

    /**
     * Input key for removing unused images
     */
    const INPUT_KEY_REMOVE_UNUSED_GALLERY = 'remove_unused_gallery';

    /**
     * Input key for listing missing files
     */
    const INPUT_KEY_LIST_MISSING = 'list_missing';

    /**
     * Input key for listing unused files
     */
    const INPUT_KEY_LIST_UNUSED = 'list_unused';

    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * @var File
     */
    protected $driverFile;

    /**
     * @var ProductMediaConfig
     */
    private $productMediaConfig;

    /**
     * @var array The file paths from the media directory
     */
    private $filePaths = [];

    /**
     * @var array The file paths from the tmp media directory
     */
    private $tmpFilePaths = [];

    /**
     * @var array The cache file paths from the media directory
     */
    private $cacheFilePaths = [];

    /**
     * @var array The media gallery paths from the DB
     */
    private $mediaGalleryPaths = [];

    /**
     * @var array The orphaned media gallery paths from the DB
     */
    private $orphanedMediaGalleryPaths = [];

    /**
     * @var array The missing file paths
     */
    private $missingFiles = [];

    /**
     * @var array The orphaned file paths
     */
    private $orphanedFiles = [];

    /**
     * @var array The unused file paths
     */
    private $unusedFiles = [];

    /**
     * @var array The temporary file paths
     */
    private $temporaryFiles = [];

    /**
     * Constructor
     *
     * @param ResourceConnection $resource
     * @param DirectoryList $directoryList
     * @param File $driverFile
     * @param ProductMediaConfig $productMediaConfig
     */
    public function __construct(
        ResourceConnection $resource,
        DirectoryList $directoryList,
        File $driverFile,
        ProductMediaConfig $productMediaConfig
    ) {
        $this->resource = $resource;
        $this->directoryList = $directoryList;
        $this->driverFile = $driverFile;
        $this->productMediaConfig = $productMediaConfig;
        parent::__construct();
    }

    /**
     * Gets the product media directory path
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    protected function getMediaDirectoryPath()
    {
        return $this->directoryList->getPath(DirectoryList::MEDIA)
            .DIRECTORY_SEPARATOR.'catalog'.DIRECTORY_SEPARATOR.'product';
    }

    /**
     * Gets the temporary product media directory path
     * @return string
     * @throws FileSystemException
     */
    protected function getTmpMediaDirectoryPath()
    {
        return $this->directoryList->getPath(DirectoryList::MEDIA)
            . DIRECTORY_SEPARATOR . $this->productMediaConfig->getBaseTmpMediaPath();
    }

    /**
     * Gets the product media directory path
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    protected function getCacheDirectoryPath()
    {
        return $this->directoryList->getPath(DirectoryList::MEDIA)
            .DIRECTORY_SEPARATOR.'catalog'.DIRECTORY_SEPARATOR.'product'.DIRECTORY_SEPARATOR.'cache';
    }

    /**
     * Gets the product placeholder directory path
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    protected function getPlaceholderDirectoryPath(): string
    {
        return $this->directoryList->getPath(DirectoryList::MEDIA)
            .DIRECTORY_SEPARATOR.'catalog'.DIRECTORY_SEPARATOR.'product'.DIRECTORY_SEPARATOR.'placeholder';
    }

    /**
     * Gets the Mirasvit SEO-friendly product image directory path
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    protected function getMirasvitSEODirectoryPath(): string
    {
        return $this->directoryList->getPath(DirectoryList::MEDIA)
            .DIRECTORY_SEPARATOR.'catalog'.DIRECTORY_SEPARATOR.'product'.DIRECTORY_SEPARATOR.'image';
    }

    /**
     * Get the file paths for all media files exclusing cache files
     * @param $useCache boolean
     * @return array|string[]
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    protected function getFilePaths($useCache = true)
    {
        if ($useCache && $this->filePaths) {
            return $this->filePaths;
        }
        $mediaDirectoryPath = $this->getMediaDirectoryPath();
        if (!$this->driverFile->isExists($mediaDirectoryPath)) {
            return [];
        }
        $this->filePaths = [];
        $excludedPaths = [
            $this->getCacheDirectoryPath(),
            $this->getMirasvitSEODirectoryPath(),
            $this->getPlaceholderDirectoryPath()
        ];
        foreach ($this->driverFile->readDirectory($mediaDirectoryPath) as $dirsPath) {
            if (!$this->driverFile->isDirectory($dirsPath) || in_array($dirsPath, $excludedPaths, true)) {
                continue; // Skip files and excluded dirs
            }

            foreach ($this->driverFile->readDirectoryRecursively($dirsPath) as $filePath) {
                if (!$this->driverFile->isDirectory($filePath)) {
                    $this->filePaths[] = $filePath;
                }
            }
        }
        return $this->filePaths;
    }

    /**
     * Get the file paths for all tmp media files
     * @param $useCache boolean
     * @return array|string[]
     * @throws FileSystemException
     */
    protected function getTmpFilePaths($useCache = true, int $maxLifetimeInHours = 0)
    {
        if ($useCache && $this->tmpFilePaths) {
            return $this->tmpFilePaths;
        }
        if ($this->driverFile->isExists($this->getTmpMediaDirectoryPath())) {
            $this->tmpFilePaths = $this->driverFile->readDirectoryRecursively($this->getTmpMediaDirectoryPath());

            $maxLifetime = $maxLifetimeInHours * 60 * 60; // in seconds

            foreach ($this->tmpFilePaths as $k => $filePath) {
                if ($this->driverFile->isDirectory($filePath)) {
                    // Skip directories
                    unset($this->tmpFilePaths[$k]);
                } elseif ($this->driverFile->isFile($filePath) && $this->driverFile->isReadable($filePath)) {
                    if ($this->getTmpMediaDirectoryPath() === $this->driverFile->getParentDirectory($filePath)) {
                        // Skip top-level files
                        unset($this->tmpFilePaths[$k]);
                        continue;
                    }

                    if ((time() - $this->driverFile->stat($filePath)['mtime']) > $maxLifetime) {
                        // Skip new files
                        unset($this->tmpFilePaths[$k]);
                    }
                }
            }
        }
        return $this->tmpFilePaths;
    }

    /**
     * Get the file paths for all cache files
     * @param $useCache boolean
     * @return array|string[]
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    protected function getCacheFilePaths($useCache = true)
    {
        if ($useCache && $this->cacheFilePaths) {
            return $this->cacheFilePaths;
        }
        if ($this->driverFile->isExists($this->getCacheDirectoryPath())) {
            $this->cacheFilePaths = array_filter(
                $this->driverFile->readDirectoryRecursively($this->getCacheDirectoryPath()),
                function ($filePath) {
                    return !$this->driverFile->isDirectory($filePath); // skip directories
                }
            );
        }
        return $this->cacheFilePaths;
    }

    /**
     * Gets an array of all the media gallery paths from the DB which are not associated to any current products
     * @param $useCache boolean
     * @return array
     */
    protected function getOrphanedMediaGalleryPaths($useCache = true)
    {
        if ($useCache && $this->orphanedMediaGalleryPaths) {
            return $this->orphanedMediaGalleryPaths;
        }
        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from(
                ['gal' => $connection->getTableName(Gallery::GALLERY_TABLE)]
            )
            ->joinLeft(
                ['ent' => $connection->getTableName(Gallery::GALLERY_VALUE_TO_ENTITY_TABLE)],
                'gal.value_id=ent.value_id'
            )
            ->reset(Select::COLUMNS)
            ->columns('value')
            ->where('ent.entity_id IS NULL');
        $this->orphanedMediaGalleryPaths = $connection->fetchCol($select);
        //add the directory path to each value because the DB only stores the path relative to the media directory
        $mediaDirectoryPath = $this->getMediaDirectoryPath();
        $this->orphanedMediaGalleryPaths = array_map(function($value) use($mediaDirectoryPath) {
            return $mediaDirectoryPath.$value;
        }, $this->orphanedMediaGalleryPaths);
        return $this->orphanedMediaGalleryPaths;
    }

    /**
     * Gets an array of all the media gallery paths from the DB
     * @param $useCache boolean
     * @return array
     */
    protected function getMediaGalleryPaths($useCache = true)
    {
        if ($useCache && $this->mediaGalleryPaths) {
            return $this->mediaGalleryPaths;
        }
        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from($connection->getTableName(Gallery::GALLERY_TABLE))
            ->reset(Select::COLUMNS)->columns('value');
        $this->mediaGalleryPaths = $connection->fetchCol($select);
        //add the directory path to each value because the DB only stores the path relative to the media directory
        $mediaDirectoryPath = $this->getMediaDirectoryPath();
        $this->mediaGalleryPaths = array_map(function($value) use($mediaDirectoryPath) {
            return $mediaDirectoryPath.$value;
        }, $this->mediaGalleryPaths);
        return $this->mediaGalleryPaths;
    }

    /**
     * Gets an array of all the media gallery entries that are not present in the file system
     * @param $useCache boolean
     * @return array
     */
    protected function getMissingFilePaths($useCache = true)
    {
        if ($useCache && $this->missingFiles) {
            return $this->missingFiles;
        }
        $filePaths = $this->getFilePaths($useCache);
        $mediaGalleryPaths = $this->getMediaGalleryPaths($useCache);
        $this->missingFiles = array_diff($mediaGalleryPaths, $filePaths);
        return $this->missingFiles;
    }

    /**
     * Gets an array of all the media gallery entries that are not present in the file system
     * @param $useCache boolean
     * @return array
     */
    protected function getOrphanedFilePaths($useCache = true)
    {
        if ($useCache && $this->orphanedFiles) {
            return $this->orphanedFiles;
        }
        $filePaths = $this->getFilePaths($useCache);
        $orphanedFilePaths = $this->getOrphanedMediaGalleryPaths($useCache);
        $this->orphanedFiles = array_intersect($filePaths, $orphanedFilePaths);
        return $this->orphanedFiles;
    }

    /**
     * Gets an array of all the files that are not present in the media gallery table
     * @param $useCache boolean
     * @return array
     */
    protected function getUnusedFilePaths($useCache = true)
    {
        if ($useCache && $this->unusedFiles) {
            return $this->unusedFiles;
        }
        $filePaths = $this->getFilePaths($useCache);
        $mediaGalleryPaths = $this->getMediaGalleryPaths($useCache);
        $this->unusedFiles = array_diff($filePaths, $mediaGalleryPaths);
        return $this->unusedFiles;
    }

    /**
     * Gets an array of all the files that are not present in the media gallery table
     * @param $useCache boolean
     * @return array
     * @throws FileSystemException
     */
    protected function getTemporaryFilePaths($useCache = true, int $maxLifetimeInHours = 0)
    {
        if ($useCache && $this->temporaryFiles) {
            return $this->temporaryFiles;
        }
        $this->temporaryFiles = $this->getTmpFilePaths($useCache, $maxLifetimeInHours);
        return $this->temporaryFiles;
    }

    /**
     * Deletes the provided media gallery paths from the database
     * @param array $mediaGalleryPaths
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    protected function deleteMediaGalleryPaths(array $mediaGalleryPaths)
    {
        //strip the media directory off the paths to get the path relative to the media directory
        $mediaDirectoryPath = $this->getMediaDirectoryPath();
        $mediaGalleryPaths = array_map(function($value) use($mediaDirectoryPath) {
            return str_replace($mediaDirectoryPath, null, $value);
        }, $mediaGalleryPaths);

        $connection = $this->resource->getConnection();
        $chunkSize = 500;

        foreach (array_chunk($mediaGalleryPaths, $chunkSize) as $mediaGalleryPathsChunk) {
            $connection->delete(
                $connection->getTableName(Gallery::GALLERY_TABLE),
                ['value IN (?)' => $mediaGalleryPathsChunk]
            );
        }
    }

    /**
     * Deletes the provided file paths from the filesystem
     * @param array $filePaths
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    protected function deleteFilePaths(array $filePaths)
    {
        foreach ($filePaths as $filePath) {
            try {
                $this->driverFile->deleteFile($filePath);
            } catch (\Magento\Framework\Exception\FileSystemException $e) {
                // Skip this exceptions
            }
        }
    }
}
