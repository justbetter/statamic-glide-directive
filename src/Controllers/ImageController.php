<?php

namespace JustBetter\GlideDirective\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use League\Glide\Server;
use League\Glide\Signatures\Signature;
use League\Glide\Signatures\SignatureException;
use Statamic\Assets\Asset;
use Statamic\Contracts\Assets\Asset as AssetContract;
use Statamic\Facades\Asset as AssetFacade;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImageController extends Controller
{
    protected null|Asset|AssetContract $asset;

    protected array $params;

    public function __construct(protected Server $server) {}

    public function getImageByPreset(
        Request $request,
        int $width,
        int $height,
        string $signature,
        string $file,
        string $format
    ): BinaryFileResponse {
        $file = ltrim($file, '/');
        $format = ltrim($format, '.');

        $this->asset = AssetFacade::findByUrl('/'.$file);

        $this->params = [
            's' => $signature,
            'width' => $width,
            'height' => $height,
            'format' => $format,
        ];

        if (! $this->asset) {
            abort(404);
        }

        try {
            $signatureFactory = new Signature(config('app.key'));

            $signatureFactory->validateRequest($this->asset->url(), [
                's' => $signature,
                'width' => $width,
                'height' => $height,
                'format' => '.'.$format,
            ]);
        } catch (SignatureException $e) {
            abort(404);
        }

        $relativePath = $this->getPublicRelativePath();
        $publicPath = public_path($relativePath);

        if (! file_exists($publicPath)) {
            $generatedPath = $this->buildImage();
            if (! $generatedPath || ! file_exists(public_path($generatedPath))) {
                abort(404);
            }

            $publicPath = public_path($generatedPath);
        }

        return new BinaryFileResponse($publicPath, 200, [
            'Content-Type' => $this->getContentType(),
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }

    protected function buildImage(): ?string
    {
        if (! $this->asset) {
            return null;
        }

        $params = [
            'w' => $this->params['width'],
            'fm' => $this->params['format'],
            'q' => 85,
        ];

        if (! empty($this->params['height'])) {
            $params['h'] = $this->params['height'];
            $params['fit'] = 'crop-focal';
        }

        $source = Storage::build([
            'driver' => 'local',
            'root' => public_path(),
        ]);

        $cacheRoot = $this->getPublicCacheRoot();

        $cache = Storage::build([
            'driver' => 'local',
            'root' => public_path($cacheRoot),
        ]);

        $this->server->setSource($source->getDriver());
        $this->server->setSourcePathPrefix('/');

        $this->server->setCache($cache->getDriver());
        $this->server->setCachePathPrefix('');

        $expectedRelativePath = $this->getCachePathInsideCacheRoot();

        $this->server->setCachePathCallable(
            fn (string $path, array $params) => $expectedRelativePath
        );

        $generated = $this->server->makeImage($this->asset->url(), $params);

        return $cacheRoot.'/'.ltrim($generated, '/');
    }

    protected function getPublicCacheRoot(): string
    {
        return trim(config('justbetter.glide-directive.cache_prefix'), '/').'/'
            .trim(config('justbetter.glide-directive.storage_prefix'), '/');
    }

    protected function getCachePathInsideCacheRoot(): string
    {
        $width = (int) $this->params['width'];
        $height = (int) $this->params['height'];
        $signature = trim($this->params['s'], '/');
        $format = ltrim($this->params['format'], '.');

        $assetUrl = ltrim($this->asset?->url() ?? '', '/');

        return $width.'/'
            .$height.'/'
            .$signature.'/'
            .$assetUrl.'.'.$format;
    }

    protected function getPublicRelativePath(): string
    {
        return $this->getPublicCacheRoot().'/'.$this->getCachePathInsideCacheRoot();
    }

    protected function getContentType(): string
    {
        return match (strtolower($this->params['format'])) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'avif' => 'image/avif',
            default => 'application/octet-stream',
        };
    }
}
