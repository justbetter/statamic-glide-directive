<?php

namespace JustBetter\GlideDirective\Controllers;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Glide\Server;
use League\Glide\Signatures\Signature;
use League\Glide\Signatures\SignatureException;
use Statamic\Assets\Asset;
use Statamic\Contracts\Assets\Asset as AssetContract;
use Statamic\Facades\Asset as AssetFacade;
use Statamic\Imaging\ImageGenerator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImageController extends Controller
{
    protected null|Asset|AssetContract $asset;

    protected array $params;

    public function __construct(protected ImageGenerator $imageGenerator, protected Server $server) {}

    public function getImageByPreset(Request $request, string $preset, string $fit, string $signature, string $file, string $format): BinaryFileResponse
    {
        $this->asset = AssetFacade::findByUrl(Str::start($file, '/'));
        $this->params = ['s' => $signature, 'p' => $preset, 'fit' => $fit, 'format' => $format];

        if (! $this->asset) {
            abort(404);
        }

        try {
            $signatureFactory = new Signature(config('app.key'));
            $signatureFactory->validateRequest($this->asset->url(), $this->params);
        } catch (SignatureException $e) {
            abort(404);
        }

        $path = $this->buildImage();
        $cachePath = config('statamic.assets.image_manipulation.cache_path');
        $publicPath = $cachePath.'/'.$path;

        if (! file_exists($publicPath)) {
            abort(404);
        }

        $contentType = $this->getContentType();

        return new BinaryFileResponse($publicPath, 200, [
            'Content-Type' => $contentType,
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }

    protected function buildImage(): ?string
    {
        if (! $this->asset) {
            return null;
        }

        $this->applyWebpDefaults();

        $this->server->setSource(Storage::build(['driver' => 'local', 'root' => public_path()])->getDriver());
        $this->server->setSourcePathPrefix('/');
        $this->server->setCachePathPrefix(config('justbetter.glide-directive.storage_prefix', 'glide-image').'/'.$this->params['p'].'/'.$this->params['fit'].'/'.$this->params['s']);
        $this->server->setCachePathCallable($this->getCachePathCallable());
        // @phpstan-ignore-next-line
        $path = $this->server->makeImage($this->asset->url(), $this->params);

        return $path;
    }

    protected function getCachePathCallable(): ?Closure
    {
        $server = $this->server;
        $asset = $this->asset;
        $params = $this->params;

        if (! $asset) {
            return null;
        }

        return function () use ($server, $asset, $params) {
            return $server->getCachePathPrefix().$asset->url().$params['format'];
        };
    }

    protected function applyWebpDefaults(): void
    {
        if (($this->params['format'] ?? null) !== '.webp') {
            return;
        }

        $presetConfig = config('statamic.assets.image_manipulation.presets')[$this->params['p']] ?? [];
        if (array_key_exists('lossless', $presetConfig)) {
            return;
        }

        $this->params['lossless'] = 0;
    }

    protected function getContentType(): string
    {
        $format = $this->params['format'] ?? '';

        return match ($format) {
            '.webp' => 'image/webp',
            '.jpg', '.jpeg' => 'image/jpeg',
            '.png' => 'image/png',
            '.gif' => 'image/gif',
            // @phpstan-ignore-next-line
            default => $this->asset ? $this->asset->mimeType() : 'application/octet-stream',
        };
    }
}
