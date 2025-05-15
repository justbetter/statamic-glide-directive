<?php

namespace JustBetter\GlideDirective\Controllers;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use JustBetter\GlideDirective\Responsive;
use League\Glide\Server;
use League\Glide\Signatures\Signature;
use League\Glide\Signatures\SignatureException;
use Statamic\Assets\Asset;
use Statamic\Contracts\Assets\Asset as AssetContract;
use Statamic\Contracts\Imaging\ImageManipulator;
use Statamic\Facades\Asset as AssetFacade;
use Statamic\Facades\Image;
use Statamic\Imaging\ImageGenerator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImageController extends Controller
{
    protected ?AssetContract $asset;

    protected array $params;

    public function __construct(protected ImageGenerator $imageGenerator, protected Server $server) {}

    public function placeholder(Request $request, string $file, string $webp = ''): Response
    {
        /** @var ?Asset $asset */
        $asset = AssetFacade::findByUrl(Str::start($file, '/'));
        $this->asset = $asset;

        if (! $this->asset) {
            abort(404);
        }

        $presets = Responsive::getPresets($this->asset);
        $base64Image = $presets['placeholder'] ?? '';
        $base64Content = preg_replace('/^data:image\/\w+;base64,/', '', $base64Image);
        $imageData = base64_decode($base64Content);
        $mimeType = $this->asset->mimeType();

        return response($imageData)
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', 'inline');
    }

    public function getImageByPreset(Request $request, string $preset, string $fit, string $signature, string $file, string $format): BinaryFileResponse
    {
        /** @var ?Asset $asset */
        $asset = AssetFacade::findByUrl(Str::start($file, '/'));
        $this->asset = $asset;
        $this->params = ['s' => $signature, 'preset' => $preset, 'fit' => $fit, 'format' => $format];

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

        $contentType = $this->asset->mimeType();

        return new BinaryFileResponse($publicPath, 200, [
            'Content-Type' => $contentType,
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }

    protected function buildImage(): ?string
    {
        if (! $this->asset) {
            return null;
        }

        $this->server->setSource(Storage::build(['driver' => 'local', 'root' => public_path()])->getDriver());
        $this->server->setSourcePathPrefix('/');
        $this->server->setCachePathPrefix(config('justbetter.glide-directive.storage_prefix', 'storage/glide-image').'/'.$this->params['preset'].'/'.$this->params['fit'].'/'.$this->params['s']);
        $this->server->setCachePathCallable($this->getCachePathCallable());

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
}
