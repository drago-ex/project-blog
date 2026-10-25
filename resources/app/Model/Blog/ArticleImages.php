<?php

declare(strict_types=1);

namespace App\Model\Blog;

use Nette\Forms\Control;
use Nette\Http\FileUpload;
use Nette\IOException;
use Nette\Utils\FileSystem;
use Nette\Utils\Image;
use Nette\Utils\ImageException;
use Nette\Utils\Random;
use Tracy\Debugger;
use Tracy\ILogger;


/**
 * Cover images of the articles, in www/upload/blog/ (web path upload/blog/). An upload is made smaller to fit
 * MaxWidth × MaxHeight, keeps its format and gets a random name, so a replaced image is never served from a cache.
 */
class ArticleImages
{
	/** Web path of the images, relative to the base path. */
	public const string Path = 'upload/blog';

	/** Largest upload in pixels; decoding takes about 4 bytes of memory per pixel. */
	public const int MaxPixels = 40_000_000;

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


	/** GD (only suggested by the package) resizes the uploads; without it no image can be uploaded. */
	public static function isSupported(): bool
	{
		return extension_loaded('gd');
	}


	/**
	 * Form rule: the image has at most MaxPixels, read from its header before it is decoded, so a huge image
	 * gets a form error instead of exhausting the memory.
	 */
	public static function validatePixels(Control $control): bool
	{
		$upload = $control->getValue();
		if (!$upload instanceof FileUpload || !$upload->isOk()) {
			return true;
		}

		$size = @getimagesize($upload->getTemporaryFile()); // @ - not an image is reported by the Image rule
		return $size === false || $size[0] * $size[1] <= self::MaxPixels;
	}


	/**
	 * Deletes an image; a name with a path is ignored, so nothing outside the directory can be deleted.
	 * A file that cannot be deleted (locked, permissions) is only logged: the article is saved or deleted anyway.
	 */
	public function delete(?string $name): void
	{
		if ($name === null || $name === '' || basename($name) !== $name) {
			return;
		}

		try {
			FileSystem::delete($this->getDir() . '/' . $name);
		} catch (IOException $e) {
			Debugger::log($e, ILogger::WARNING);
		}
	}


	private function getDir(): string
	{
		return $this->wwwDir . '/' . self::Path;
	}
}
