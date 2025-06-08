<?php
// 应用公共文件


use think\facade\Config;

if ( !function_exists('setting') )
{
    /**
     * 设置相关助手函数
     * @param string|array $name
     * @param null $value
     * @return array|bool|mixed|null
     */
    function setting($name = '', $value = null)
    {
        if ( $value === null && is_string($name) )
        {
            //获取一级配置
            if ( '.' === substr($name, -1) )
            {
                $result = Config::get(substr($name, 0, -1));
                if ( count($result) === 0 )
                {
                    //如果文件不存在，查找数据库
                    $result = get_database_setting(substr($name, 0, -1));
                }

                return $result;
            }
            //判断配置是否存在或读取配置
            if ( 0 === strpos($name, '?') )
            {
                $result = Config::has(substr($name, 1));
                if ( !$result )
                {
                    //如果配置不存在，查找数据库
                    if ( $name && false === strpos($name, '.') )
                    {
                        return [];
                    }

                    if ( '.' === substr($name, -1) )
                    {

                        return get_database_setting(substr($name, 0, -1));
                    }

                    $name_arr    = explode('.', $name);
                    $name_arr[0] = strtolower($name_arr[0]);

                    $result = get_database_setting($name_arr[0]);
                    if ( $result )
                    {
                        $config = $result;
                        // 按.拆分成多维数组进行判断
                        foreach ( $name_arr as $val )
                        {
                            if ( isset($config[$val]) )
                            {
                                $config = $config[$val];
                            }
                            else
                            {
                                return null;
                            }
                        }

                        return $config;

                    }
                    return $result;
                }

                return true;
            }

            $result = Config::get($name);
            if ( !$result )
            {
                $result = get_database_setting($name);
            }
            return $result;
        }
        return Config::set($name, $value);
    }

}

if ( !function_exists('get_database_setting') )
{
    /**
     * 获取数据库配置
     * @param $name
     * @return array
     */
    function get_database_setting($name): array
    {
        $result = [];
        $group  = (new app\common\model\SettingGroup)->where('code', $name)->findOrEmpty();
        if ( !$group->isEmpty() )
        {
            $result = [];
            foreach ( $group->setting as $key => $setting )
            {
                $key_setting = [];
                foreach ( $setting->content as $content )
                {
                    $key_setting[$content['field']] = $content['content'];
                }
                $result[$setting->code] = $key_setting;
            }
        }

        return $result;
    }
}


if ( !function_exists('format_size') )
{
    /**
     * 格式化文件大小单位
     * @param $size
     * @param string $delimiter
     * @return string
     */
    function format_size($size, string $delimiter = ''): string
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
        for ( $i = 0; $size >= 1024 && $i < 5; $i++ )
        {
            $size /= 1024;
        }
        return round($size, 2) . $delimiter . $units[$i];
    }
}

if ( !function_exists('htmlentities_view') )
{
    /**
     * 封装默认的 htmlentities 函数，避免在php8.1环境中view传入null报错
     * @param mixed $string
     * @return string
     */
    function htmlentities_view($string): string
    {
        return htmlentities((string)$string);
    }
}

if (!function_exists('hsv2rgb'))
{
    function hsv2rgb($h, $s, $v): array
    {
        $r = $g = $b = 0;

        $i = floor($h * 6);
        $f = $h * 6 - $i;
        $p = $v * (1 - $s);
        $q = $v * (1 - $f * $s);
        $t = $v * (1 - (1 - $f) * $s);

        switch ($i % 6)
        {
            case 0:
                $r = $v;
                $g = $t;
                $b = $p;
                break;
            case 1:
                $r = $q;
                $g = $v;
                $b = $p;
                break;
            case 2:
                $r = $p;
                $g = $v;
                $b = $t;
                break;
            case 3:
                $r = $p;
                $g = $q;
                $b = $v;
                break;
            case 4:
                $r = $t;
                $g = $p;
                $b = $v;
                break;
            case 5:
                $r = $v;
                $g = $p;
                $b = $q;
                break;
        }

        return [
            floor($r * 255),
            floor($g * 255),
            floor($b * 255),
        ];
    }
}

if ( !function_exists('letter_avatar') )
{
    /**
     * 首字母头像
     *
     * @param $text
     *
     * @return string
     */
    function letter_avatar($text): string
    {
        $total = unpack('L', hash('adler32', $text, true))[1];
        $hue   = $total % 360;
        list($r, $g, $b) = hsv2rgb($hue / 360, 0.3, 0.9);

        $bg    = "rgb($r,$g,$b)";
        $color = "#ffffff";
        $first = mb_strtoupper(mb_substr($text, 0, 1));
        $src   = base64_encode(
            '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" height="100" width="100"><rect fill="' . $bg . '" x="0" y="0" width="100" height="100"></rect><text x="50" y="50" font-size="50" text-copy="fast" fill="' . $color
            . '" text-anchor="middle" text-rights="admin" dominant-baseline="central">' . $first . '</text></svg>'
        );
        return 'data:image/svg+xml;base64,' . $src;
    }
}