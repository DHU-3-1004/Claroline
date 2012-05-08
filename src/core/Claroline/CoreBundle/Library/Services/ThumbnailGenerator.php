<?php

namespace Claroline\CoreBundle\Library\Services;

class ThumbnailGenerator
{
    /** @var string */
    private $dir;
    
    /** @var bool */
    private $hasGdExtension;
    
    /** @var bool */
    private $hasFfmpegExtension;
    
    const WIDTH = 50;
    const HEIGHT = 50;
    
    public function __construct ($dir)
    {
        $this->dir = $dir;
        
        if (!extension_loaded('gd')) 
        {
            if (!dl('gd.so')) 
            {
                $this->hasGdExtension = false;
            }
        }
        else
        {
            $this->hasGdExtension = true;
            //echo "gd extension is loaded\n";
        }

        if (!extension_loaded('ffmpeg')) 
        {
            if (!dl('ffmpeg.so')) 
            {
                $this->hasFfmpegExtension = false;
            }
        }
        else
        {
            $this->hasFfmpegExtension = true;
           // echo "ffmpeg extension is loaded\n";
        }
    }
    
    //the end could be refactored
    public function createThumb($name, $filename, $newWidth, $newHeight)
    { 
        $extension = pathinfo($name, PATHINFO_EXTENSION);
        
        if($this->hasGdExtension)
        {
            switch($extension)
            {
                case "jpeg":
                    $srcImg = imagecreatefromjpeg($name);
                    $filename = preg_replace('"\.jpeg$"', '@'.self::WIDTH.'x'.self::HEIGHT.'.png', $filename);
                    break;
                case "jpg":
                    $srcImg = imagecreatefromjpeg($name);
                    $filename = preg_replace('"\.jpg$"', '@'.self::WIDTH.'x'.self::HEIGHT.'.png', $filename);
                    break;
                case "png":    
                    $srcImg = imagecreatefrompng($name);
                    $filename = preg_replace('"\.png$"', '@'.self::WIDTH.'x'.self::HEIGHT.'.png', $filename);
                    break;
                case "mov":
                    $srcImg = $this->createMpegGDI($name);
                    $filename = preg_replace('"\.mov$"', '@'.self::WIDTH.'x'.self::HEIGHT.'.png', $filename);
                    break;
                case "mp4":
                    $srcImg = $this->createMpegGDI($name);
                    $filename = preg_replace('"\.mp4$"', '@'.self::WIDTH.'x'.self::HEIGHT.'.png', $filename);
                    break;
                default:
                    return null;
            }

            return $this->createThumbNail($newWidth, $newHeight, $srcImg, $filename);
            
            //imagedestroy($dstImg); 
            //imagedestroy($srcImg);
        }
        else
        {
            return "gdExtension is missing";
        }
    }
    
    public function createThumbNail($newWidth, $newHeight, $srcImg, $filename)
    {
        $oldX = imagesx($srcImg);
        $oldY = imagesy($srcImg);

        if ($oldX > $oldY) 
        {
            $thumbWidth = $newWidth;
            $thumbHeight = $oldY*($newHeight/$oldX);
        }
        else
        {
            if ($oldX < $oldY) 
            {
                $thumbWidth = $oldX*($newWidth/$oldY);
                $thumbHeight = $newHeight;
            }
        }

        $dstImg = imagecreatetruecolor($thumbWidth, $thumbHeight);
        imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $oldX, $oldY);

        return $srcImg = imagepng($dstImg, $filename); 
    }
    
    public function parseAllAndGenerate()
    {
        $iterator = new \DirectoryIterator($this->dir);
        $i=0;
          
        foreach($iterator as $fileInfo)
        {
            if($fileInfo->isFile())
            {      
                $pathName = $fileInfo->getPathname();
                $path = $fileInfo->getPath();
                $fileName = $fileInfo->getFileName();
                $this->createThumb("{$pathName}", "{$path}/thumbs/tn@{$fileName}", self::WIDTH, self::HEIGHT);
                
                echo("{$i}: thumbnail {$fileName} \n");
                $i++;
                
            }
        }
    }
    
    private function createMpegGDI($name)
    {
        if($this->hasFfmpegExtension)
        {
            $media = new \ffmpeg_movie($name);
            $frameCount = $media->getFrameCount();
            $frame = $media->getFrame(round($frameCount / 2));
            $gdImage = $frame->toGDImage();

            return $gdImage;
        }
        else
        {
            return "ffMpegExtension is missing";
        }
    }
   
}