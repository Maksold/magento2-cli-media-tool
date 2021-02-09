<?php
/**
 * @package     MagentoCode\CliMediaTool
 * @author      Brian Neff <bneff84@gmail.com>
 * @license     MIT
 */
namespace MagentoCode\CliMediaTool\Command;

use Magento\Framework\DB\Select;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Catalog\Model\ResourceModel\Product\Gallery;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\App\Filesystem\DirectoryList;
use Symfony\Component\Console\Input\InputOption;

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
     * @var array The file paths from the media directory
     */
    private $filePaths = [];

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
     * Constructor
     *
     * @param ResourceConnection $resource
     */
    public function __construct(ResourceConnection $resource, DirectoryList $directoryList, File $driverFile)
    {
        $this->resource = $resource;
        $this->directoryList = $directoryList;
        $this->driverFile = $driverFile;
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
        if ($this->driverFile->isExists($this->getMediaDirectoryPath())) {
            $this->filePaths = $this->driverFile->readDirectoryRecursively($this->getMediaDirectoryPath());
            $cacheFiles = $this->getCacheFilePaths();
            $this->filePaths = array_diff($this->filePaths, $cacheFiles);
            //remove anything that's a directory
            foreach ($this->filePaths as $k => $filePath) {
                if ($this->driverFile->isDirectory($filePath)) {
                    unset($this->filePaths[$k]);
                }
            }
        }
        return $this->filePaths;
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
            $this->cacheFilePaths = $this->driverFile->readDirectoryRecursively($this->getCacheDirectoryPath());
            //remove anything that's a directory
            foreach ($this->cacheFilePaths as $k => $cacheFilePath) {
                if ($this->driverFile->isDirectory($cacheFilePath)) {
                    unset($this->cacheFilePaths[$k]);
                }
            }
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
        $select = $connection->select()
            ->from($connection->getTableName(Gallery::GALLERY_TABLE))
            ->where('value IN(?)', $mediaGalleryPaths);

        $delete = $connection->deleteFromSelect(
            $select,
            $connection->getTableName(Gallery::GALLERY_TABLE)
        );
        $connection->query($delete);
    }

    /**
     * Deletes the provided file paths from the filesystem
     * @param array $filePaths
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    protected function deleteFilePaths(array $filePaths)
    {
        array_map([$this->driverFile, 'deleteFile'], $filePaths);
    }
}
