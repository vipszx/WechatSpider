<?php
/**
 * Created by PhpStorm.
 * User: szx
 * Date: 2018/3/4
 * Time: v3.0
 */

namespace szx\WechatSpider;

use Sunra\PhpSimple\HtmlDomParser;

class WechatSpider
{
    private $config;

    public function __construct(array $config = [])
    {
        if (!is_array($config)) {
            return false;
        }
        $defaults = [
            'save_img' => true,
            'save_path' => './',
            'time_out' => 10,
            'headers' => [
                'User-Agent:mozilla/5.0 (Linux; U; Android 5.1; zh-cn; OPPO R9tm Build/LMY47I) AppleWebKit/537.36 (KHTML, like Gecko)Version/4.0 Chrome/37.0.0.0 MQQBrowser/7.5 Mobile Safari/537.36',
                'Referer:https://mp.weixin.qq.com/'
            ]];
        $this->config = $config + $defaults;
    }

    public function getInfo($html)
    {
        $dom = HtmlDomParser::str_get_html($html);
        $title = $dom->find('title', 0)->plaintext;//标题
        $date = $dom->find('#post-date', 0)->plaintext;//日期
        $imgs = $dom->find('img');//图片
        $videos = $dom->find('.video_iframe');//视频

        $url = 'http://read.html5.qq.com/image?src=forum&q=4&r=0&imgflag=7&imageUrl=';
        if ($this->config['save_img'] === true) {
            foreach ($imgs as $img) {
                $data_src = $img->getAttribute('data-src');
                if (!$data_src) {
                    continue;
                }
                $src = $url . $data_src;
                $type = $img->getAttribute('data-type');
                if (!$type) {
                    $type = 'jpg';
                }
                $path = $this->config['save_path'];
                $filename = microtime() . '.' . $type;
                $this->curlDownFile($src, $path, $filename);
                $img->setAttribute('src', $path . $filename);
                $img->removeAttribute('data-src');
            }
        }
        foreach ($videos as $video) {
            $data_src = $video->getAttribute('data-src');
            $video->setAttribute('src', $data_src);
            $video->removeAttribute('data-src');
        }
        $content = $dom->find('#js_content', 0)->outertext;//正文内容
        return compact('title', 'date', 'content');
    }

    public function curlDownFile($img_url, $save_path = '', $filename = '')
    {
        if (trim($img_url) == '') {
            return false;
        }
        if (trim($save_path) == '') {
            $save_path = './';
        }
        if (trim($filename) == '') {
            return false;
        }

        //创建保存目录
        if (!file_exists($save_path) && !mkdir($save_path, 0755, true)) {
            return false;
        }

        // curl下载文件
        $ch = curl_init();
        $timeout = $this->config['time_out'];
        $headers = $this->config['headers'];
        curl_setopt($ch, CURLOPT_URL, $img_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $img = curl_exec($ch);
        curl_close($ch);

        // 保存文件到制定路径
        file_put_contents($filename, $img);

        unset($img, $url);
        return true;
    }
}
