<?php
namespace app\common\taglib;

use think\template\TagLib;

class Bear extends TagLib
{
    /**
     * 定义标签列表
     */
    protected $tags = [
        // 标签定义： attr 属性列表 close 是否闭合（0 或者1 默认1） alias 标签别名 level 嵌套层次
        'status' => ['attr' => 'status,text', 'close' => 0],
        'badge' => ['attr' => 'text,type', 'close' => 0], // type可以是：primary,success,info,warning,danger
    ];

    /**
     * 状态显示标签
     * @param array $tag 标签属性
     * @return string
     */
    public function tagStatus(array $tag): string
    {
        $status = $this->autoBuildVar($tag['value']);
        $text = $this->autoBuildVar($tag['text']);

        $parseStr = '<?php ';
        $parseStr .= '$status = ' . $status . ';';
        $parseStr .= '$text = ' . $text . ';';
        $parseStr .= 'if($status == 1): ?>';
        $parseStr .= '<span class="text-success"><?php echo $text; ?></span>';
        $parseStr .= '<?php else: ?>';
        $parseStr .= '<span class="text-danger"><?php echo $text; ?></span>';
        $parseStr .= '<?php endif; ?>';
        
        return $parseStr;
    }

    /**
     * Badge标签
     * @param array $tag 标签属性
     * @return string
     */
    public function tagBadge(array $tag): string
    {
        $text = $this->autoBuildVar($tag['text']);
        $type = isset($tag['type']) ? $this->autoBuildVar($tag['type']) : "'primary'";
        
        $parseStr = '<?php ';
        $parseStr .= '$text = ' . $text . ';';
        $parseStr .= '$type = ' . $type . ';';
        $parseStr .= 'echo "<span class=\"badge bg-" . $type . "\">" . $text . "</span>";';
        $parseStr .= ' ?>';
        
        return $parseStr;
    }
} 