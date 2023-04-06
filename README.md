# poster
二维码海报，图片海报，图片合成，二维码合成海报

# 安装
composer require mkcrab/poster

      config 结构说明
      $config = [
          'bg_url' => '',//海报背景图片
           'text' => [
               'text' => '', //显示文本
               'left' => 0, //左边距,数字或者center,水平居中
               'top' => 0, //上边距,数字或者center,垂直居中
               'width' => 0, //文本框宽度，设置后可实现文字换行
               'fontSize' => 32, //字号
               'fontColor' => '255,255,255', //字体颜色
               'angle' => 0, //倾斜角度
           ],
           'image' => [
               'url' => '', //图片路径 （网络图片，本地图片）
               'stream' => 0, //图片数据流，与url二选一
               'left' => 0, //左边距
               'top' => 0, //上边距
               'right' => 0, //有边距
               'bottom' => 0, //下边距
               'width' => 0, //宽
               'height' => 0, //高
               'radius' => 0, //圆角度数为显示宽度的一半
               'opacity' => 100, //透明度
           ]
     ]
     
     use Mkcrab\Poster\Poster;
     
      /**
     * 合并生成海报 示例
     */
     public function poster()
     {      
        $config = [
            'bg_url' => 'https://s1.ax1x.com/2023/04/06/ppom2PP.png',
            'text' => [
                [
                    'text' => '昵称',
                    'left' => 112,
                    'top' => 68,
                    'width' => 0,
                    'fontSize' => 20,
                    'fontColor' => '255,255,255',
                    'angle' => 0,
                ],
            ],
            'image' => [
                [
                    'url' => 'https://s1.ax1x.com/2023/04/06/pponVMD.png',
                    'stream' => 0, 
                    'left' => 20, 
                    'top' => 30,
                    'right' => 0, 
                    'bottom' => 0, 
                    'width' => 68, 
                    'height' => 68, 
                    'radius' => 34, 
                    'opacity' => 100, 
                ],
            ]
        ];
        $path = BASE_PATH . '/public/' . time() . '.png';
        $poster = new Poster();
        $poster->poster($config, $path);
        return $path;
     }

ps： config文字text和image为二维数组，图片圆角度数为显示宽度的一半
