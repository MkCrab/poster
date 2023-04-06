<?php

namespace Mkcrab\Poster;

/**
 * Class PosterFactory
 * Author MK 生成海报
 * @package App\Factory
 */
class Poster
{

    /**
     * 配置
     * @var array
     */
    public $config = [];

    /**
     * 错误信息
     * @var string
     */
    public $error = '';

    /**
     * 背景
     * @var string
     */
    private $backGroundImage = '';

    /**
     * 海拔数据
     * @var null
     */
    private $bgImageData = null;

    /**
     * 文字组
     * @var array
     */
    private $textDefault = [
        'name' => '', //图片名称，用于出错时定位
        'url' => '', //图片路径
        'stream' => 0, //图片数据流，与url二选一
        'left' => 0, //左边距
        'top' => 0, //上边距
        'right' => 0, //有边距
        'bottom' => 0, //下边距
        'width' => 0, //宽
        'height' => 0, //高
        'radius' => 0, //圆角度数，最大值为显示宽度的一半
        'opacity' => 100, //透明度
    ];

    /**
     * 图片组
     * @var array
     */
    private $imageDefault = [
        'text' => '', //显示文本
        'left' => 0, //左边距,数字或者center,水平居中
        'top' => 0, //上边距,数字或者center,垂直居中
        'width' => 0, //文本框宽度，设置后可实现文字换行
        'fontSize' => 32, //字号
        'fontColor' => '255,255,255', //字体颜色
        'angle' => 0, //倾斜角度
    ];

    /**
     * 海报配置信息
     * @param $config array 指定的配置信息
     * 合并后的完整配置信息
     */
    public function config($config = [])
    {
        $this->backGroundImage = isset($config['bg_url']) ? $config['bg_url'] : '';
        if (isset($config['image']) && $config['image']) {
            foreach ($config['image'] as $k => $v) {
                $this->config['image'][$k] = array_merge($this->imageDefault, $v);
            }
        } else {
            $this->config['image'] = [];
        }
        if (isset($config['text']) && $config['text']) {
            foreach ($config['text'] as $k => $v) {
                $this->config['text'][$k] = array_merge($this->textDefault, $v);
            }
        } else {
            $this->config['text'] = [];
        }
        return $this;
    }

    /**
     * 合并生成海报
     * @param $file string 指定生成的图片路径
     * @return string or bool 图片数据流或者处理结果状态
     */
    public function make($file = '')
    {
        if (!$this->backGroundImage || ((strpos($this->backGroundImage, 'http') === false) && !is_file($this->backGroundImage))) {
            $this->error = '未设置背景图片';
            return false;
        }
        // 处理背景
        if (!$this->bgImageData) {
            $backgroundInfo = getimagesize($this->backGroundImage);
            $backgroundFun = 'imagecreatefrom' . image_type_to_extension($backgroundInfo[2], false);
            $bgData = $backgroundFun($this->backGroundImage);
            $backgroundWidth = imagesx($bgData); //背景宽度
            $backgroundHeight = imagesy($bgData); //背景高度
            $this->bgImageData = imageCreatetruecolor($backgroundWidth, $backgroundHeight);
            //创建透明背景色，主要127参数，其他可以0-255，因为任何颜色的透明都是透明
            $transparent = imagecolorallocatealpha($this->bgImageData, 0, 0, 0, 127);
            //指定颜色为透明
            imagecolortransparent($this->bgImageData, $transparent);
            //保留透明颜色
            imagesavealpha($this->bgImageData, true);
            //填充图片颜色
            imagefill($this->bgImageData, 0, 0, $transparent);
            imagecopyresampled($this->bgImageData, $bgData, 0, 0, 0, 0, $backgroundWidth, $backgroundHeight, $backgroundWidth, $backgroundHeight);
        }

        $bgImgData = $this->bgImageData;

        //处理图片
        if ($this->config['image']) {
            foreach ($this->config['image'] as $val) {
                if ($val['stream']) { //如果传的是字符串图像流
                    $info = getimagesizefromstring($val['stream']);
                    $res = imagecreatefromstring($val['stream']);
                } elseif ($val['url'] && (strpos($val['url'], 'http') !== FALSE)) {
                    $data = file_get_contents($val['url']);
                    if (!$data) {
                        $this->error = '读取[' . $val['name'] . ']图片失败';
                        return false;
                    }
                    $info = getimagesizefromstring($data);
                    $res = imagecreatefromstring($data);
                } else {
                    if (!$val['url'] || ((strpos($val['url'], 'http') === FALSE) && !is_file($val['url']))) {
                        $this->error = '[' . $val['name'] . ']图片不存在';
                        return false;
                    }
                    $info = getimagesize($val['url']);
                    $function = 'imagecreatefrom' . image_type_to_extension($info[2], false);
                    if (!function_exists($function)) {
                        $this->error = '[' . $val['name'] . ']图片格式不支持';
                        return false;
                    }
                    $res = $function($val['url']);
                }
                imagesavealpha($res, true); //这里很重要;
                $resWidth = $info[0];
                $resHeight = $info[1];

                if ($val['radius']) {
                    if ($val['width'] > $resWidth) {
                        $val['width'] = $resWidth;
                    }
                    if ($val['height'] > $resHeight) {
                        $val['height'] = $resHeight;
                    }
                    if ($val['radius'] > round($val['width'] / 2)) {
                        $this->error = '[' . $val['name'] . ']的圆角度数最大不能超过：' . (round($val['width'] / 2));
                        return false;
                    }
                    $canvas = $this->setRadiusImage($res, $resWidth, $resHeight, $val['width'], $val['height'], $val['radius']);
                } else {
                    $canvas = imagecreatetruecolor($val['width'], $val['height']);
                    //创建透明背景色，主要127参数，其他可以0-255，因为任何颜色的透明都是透明
                    $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
                    //指定颜色为透明
                    imagecolortransparent($canvas, $transparent);
                    //保留透明颜色
                    imagesavealpha($canvas, true);
                    //填充图片颜色
                    imagefill($canvas, 0, 0, $transparent);
                    //关键函数，参数（目标资源，源，目标资源的开始坐标x,y, 源资源的开始坐标x,y,目标资源的宽高w,h,源资源的宽高w,h）
                    imagecopyresampled($canvas, $res, 0, 0, 0, 0, $val['width'], $val['height'], $resWidth, $resHeight);
                }
                $val['left'] = $val['left'] < 0 ? $backgroundWidth - abs($val['left']) - $val['width'] : $val['left'];
                $val['top'] = $val['top'] < 0 ? $backgroundHeight - abs($val['top']) - $val['height'] : $val['top'];
                //放置图像
                imagecopymerge($bgImgData, $canvas, $val['left'], $val['top'], $val['right'], $val['bottom'], $val['width'], $val['height'], $val['opacity']); //左，上，右，下，宽度，高度，透明度
            }
        }

        //处理文字
        if ($this->config['text']) {
            mb_internal_encoding("UTF-8"); // 设置编码
            foreach ($this->config['text'] as $val) {

                $fontPath = $this->getFontPath();
                if ($val['width']) {
                    $val['text'] = $this->stringAutoWrap($val['text'], $val['fontSize'], $val['angle'], $fontPath, $val['width'], false);
                }
                list($R, $G, $B) = explode(',', $val['fontColor']);
                $fontColor = imagecolorallocate($bgImgData, $R, $G, $B);
                $val['left'] = $val['left'] < 0 ? $backgroundWidth - abs($val['left']) : $val['left'];
                $val['top'] = $val['top'] < 0 ? $backgroundHeight - abs($val['top']) : $val['top'];
                if ($val['left'] == 'center' || $val['top'] == 'center') {
                    $fontBox = imagettfbbox($val['fontSize'], 0, $fontPath, $val['text']);
                    if ($val['left'] === 'center') {
                        $val['left'] = ceil(($backgroundWidth - $fontBox[2]) / 2);
                    }
                    if ($val['top'] === 'center') {
                        $val['top'] = ceil(($backgroundHeight - $fontBox[1] - $fontBox[7]) / 2);
                    }
                }
                imagettftext($bgImgData, $val['fontSize'], $val['angle'], $val['left'], $val['top'], $fontColor, $fontPath, $val['text']);
            }
        }
        if ($file) {
            $res = ImagePng($bgImgData, $file, 8); //保存到本地
            ImageDestroy($bgImgData);
            return true;
        }
        $this->error = '图片生成失败';
        return false;
    }

    /**
     * getFontPath 字体
     * @return string
     */
    public function getFontPath()
    {
        return dirname(__FILE__) . '/ttf/fonts.ttf';
    }

    /**
     * 抛出异常信息
     * @return string 异常信息说明
     */
    public function getErrorMessage()
    {
        return $this->error;
    }

    /**
     * 根据文字长度计算出行数
     * @param $string string 需要显示的文字
     * @param $info array 文字显示设置
     * @return int 返回文字行数
     */
    public function getFontLines($string, $info)
    {
        $arr = ['fontSize', 'angle', 'width'];
        $setting = [];
        foreach ($arr as $key) {
            $setting[$key] = isset($info[$key]) ? $info[$key] : $this->textDefault[$key];
        }
        return $this->stringAutoWrap($string, $setting['fontSize'], $setting['angle'], $this->getFontPath(), $setting['width'], true);
    }

    /**
     * @param $imgData
     * @param $resWidth
     * @param $resHeight
     * @param $w
     * @param $h
     * @param int $radius
     * setRadiusImage 生成圆角图片
     * @return false|resource
     */
    private function setRadiusImage(&$imgData, $resWidth, $resHeight, $w, $h, $radius = 10)
    {
        $img = imagecreatetruecolor($w, $h);
        //创建透明背景色，主要127参数，其他可以0-255，因为任何颜色的透明都是透明
        $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
        //指定颜色为透明
        imagecolortransparent($img, $transparent);
        //保留透明颜色
        imagesavealpha($img, true);
        //填充图片颜色
        imagefill($img, 0, 0, $transparent);
        imagecopyresampled($imgData, $imgData, 0, 0, 0, 0, $w, $h, $resWidth, $resHeight); //将原图缩放尺寸重新获得数据流
        $r = $radius; //圆 角半径
        for ($x = 0; $x < $w; $x++) {
            for ($y = 0; $y < $h; $y++) {
                $rgbColor = imagecolorat($imgData, $x, $y);
                if (($x >= $radius && $x <= ($w - $radius)) || ($y >= $radius && $y <= ($h - $radius))) {
                    //不在四角的范围内,直接画
                    imagesetpixel($img, $x, $y, $rgbColor);
                } else {
                    //在四角的范围内选择画
                    //上左
                    $yx1 = $r; //圆心X坐标
                    $yy1 = $r; //圆心Y坐标
                    if (((($x - $yx1) * ($x - $yx1) + ($y - $yy1) * ($y - $yy1)) <= ($r * $r))) {
                        imagesetpixel($img, $x, $y, $rgbColor);
                    }
                    //上右
                    $yx2 = $w - $r; //圆心X坐标
                    $yy2 = $r; //圆心Y坐标
                    if (((($x - $yx2) * ($x - $yx2) + ($y - $yy2) * ($y - $yy2)) <= ($r * $r))) {
                        imagesetpixel($img, $x, $y, $rgbColor);
                    }
                    //下左
                    $yx3 = $r; //圆心X坐标
                    $yy3 = $h - $r; //圆心Y坐标
                    if (((($x - $yx3) * ($x - $yx3) + ($y - $yy3) * ($y - $yy3)) <= ($r * $r))) {
                        imagesetpixel($img, $x, $y, $rgbColor);
                    }
                    //下右
                    $yx4 = $w - $r; //圆心X坐标
                    $yy4 = $h - $r; //圆心Y坐标
                    if (((($x - $yx4) * ($x - $yx4) + ($y - $yy4) * ($y - $yy4)) <= ($r * $r))) {
                        imagesetpixel($img, $x, $y, $rgbColor);
                    }
                }
            }
        }
        return $img;
    }

    /**
     * @param $string
     * @param $fontsize
     * @param $angle
     * @param $fontFace
     * @param $width
     * @param bool $returnLines
     * stringAutoWrap 文字换行
     * @return int|string
     */
    private function stringAutoWrap($string, $fontsize, $angle, $fontFace, $width, $returnLines = false)
    {
        $arr = [];
        $newStr = '';
        $counts = 1;
        $count = mb_strlen($string, 'UTF-8');
        for ($i = 0; $i < $count; $i++) {
            $str = mb_substr($string, $i, 1);
            $newStr .= $str;
            $box = imagettfbbox($fontsize, $angle, $fontFace, $newStr);
            if (($box[2] > $width)) {
                $arr[] = PHP_EOL;
                $counts += 1;
                $newStr = '';
            }
            $arr[] = $str;
        }
        if ($returnLines) {
            return $counts;
        } else {
            return trim(implode('', $arr), PHP_EOL);
        }
    }

    /**
     * @param string $file 图片
     * 图片转 base64
     */
    public function fileToBase64($file)
    {
        $img = file_get_contents($file);
        list($width, $height, $type, $attr) = getimagesize($file);
        $base64Content = base64_encode($img);
        switch ($type) {
            case 1:
                $type = "gif";
                break;
            case 2:
                $type = "jpg";
                break;
            case 3:
                $type = "png";
                break;
            default:
                $this->error = '未知图片格式';
                return false;
        }
        return 'data:image/' . $type . ';base64,' . $base64Content;
    }

    /**
     * @param string $base64 图片base64
     * @param string $url 保存本地url
     * base64 转图片 base64ToFile
     */
    public function base64ToFile($url, $base64)
    {
        $base64Arr = explode(',', $base64);
        if (isset($base64Arr[1])) {
            $data = base64_decode($base64Arr[1]);
            file_put_contents($url, $data);
            return $url;
        }
        $this->error = 'base64图片格式错误';
        return false;
    }
}
