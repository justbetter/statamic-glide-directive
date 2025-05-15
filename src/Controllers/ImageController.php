<?php

namespace JustBetter\GlideDirective\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use JustBetter\GlideDirective\Responsive;
use League\Glide\Signatures\Signature;
use League\Glide\Signatures\SignatureException;
use Statamic\Assets\Asset;
use Statamic\Contracts\Assets\Asset as AssetContract;
use Statamic\Contracts\Imaging\ImageManipulator;
use Statamic\Facades\Asset as AssetFacade;
use Statamic\Facades\Image;
use Statamic\Imaging\GlideImageManipulator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImageController extends Controller
{
    public function getPreset(Request $request, string $preset, string $fit, string $signature, string $file, string $format): BinaryFileResponse
    {
        /** @var ?Asset $asset */
        $asset = AssetFacade::findByUrl(Str::start($file, '/'));

        if (! $asset) {
            abort(404);
        }

        try {
            $signatureFactory = new Signature(config('app.key'));
            $signatureFactory->validateRequest($asset->url(), ['s' => $signature, 'preset' => $preset, 'fit' => $fit, 'format' => $format]);
        } catch (SignatureException $e) {
            abort(404);
        }

        /** @var GlideImageManipulator $manipulator */
        $manipulator = self::getManipulator($asset, $preset, $fit, $format);
        $path = $manipulator->build();

        $publicPath = public_path($path);

        if (! file_exists($publicPath)) {
            abort(404);
        }

        $contentType = $asset->mimeType();

        return new BinaryFileResponse($publicPath, 200, [
            'Content-Type' => $contentType,
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }

    protected static function getManipulator(AssetContract $asset, string $preset, string $fit, ?string $format = null): ImageManipulator|string
    {
        $manipulator = Image::manipulate($asset);
        collect(['p' => $preset, 'fm' => $format, 'fit' => $fit])->each(fn (?string $value, ?string $param) => $manipulator->$param($value));

        return $manipulator;
    }

    public function placeholder(Request $request, string $file, string $webp = ''): Response
    {
        /** @var ?Asset $asset */
        $asset = AssetFacade::findByUrl(Str::start($file, '/'));

        if (! $asset) {
            abort(404);
        }

        $presets = Responsive::getPresets($asset);
        $base64Image = $presets['placeholder'] ?? '';
        $base64Content = preg_replace('/^data:image\/\w+;base64,/', '', $base64Image);
        $imageData = base64_decode($base64Content);
        $mimeType = $asset->mimeType();

        return response($imageData)
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', 'inline');
    }
}
