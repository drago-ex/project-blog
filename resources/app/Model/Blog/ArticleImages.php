<?php

declare(strict_types=1);

namespace App\Model\Blog;

use Nette\Http\FileUpload;
use Nette\Utils\FileSystem;
use Nette\Utils\Image;
use Nette\Utils\ImageException;
use Nette\Utils\Random;


/**
 * Cover images of the articles, in www/upload/blog/ (web path upload/blog/). An upload is made smaller to fit
 * MaxWidth × MaxHeight, keeps its format and gets a random name, so a replaced image is never served from a cache.
 */
class ArticleImages
{
	/** Web path of the images, relative to the base path. */
	public const string Path = 'upload/blog';

	private const int MaxWidth = 1600;
	private const int MaxHeight = 1200;


	public function __construct(
		private readonly string $wwwDir,
	) {
	}


	/**
	 * Saves an uploaded image and returns its file name.
	 * @throws ImageException
	 */
	public function save(FileUpload $upload): string
	{
		$image = $upload->toImage();
		$image->resize(self::MaxWidth, self::MaxHeight, Image::ShrinkOnly);

		$name = Random::generate(16) . '.' . ($upload->getSuggestedExtension() ?? 'jpg');
		FileSystem::createDir($this->getDir());
		$image->save($this->getDir() . '/' . $name);
		return $name;
	}


	/** Deletes an image; a name with a path is ignored, so nothing outside the directory can be deleted. */
	public function delete(?string $name): void
	{
		if ($name !== null && $name !== '' && basename($name) === $name) {
			FileSystem::delete($this->getDir() . '/' . $name);
		}
	}


	private function getDir(): string
	{
		return $this->wwwDir . '/' . self::Path;
	}
}
