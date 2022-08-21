<?php

namespace App\Console\Commands;

use EasyWeChat\Factory;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class PushWechatOfficialMsg extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'push:wechatofficialmsg';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '推送公众号消息';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Log::info('开始推送 ' . date('Y-m-d H:i:s'));
        $appids = explode(',', config('easywechat.official_account.default.test_appid'));
        if (empty($appids)) {
            Log::info('推送失败：没有需要推送的用户');
        }

        // 获取天气信息
        $weather_info = $this->getWeather();
        // 获取毒鸡汤
        $tainted_chicken_soup = $this->getTaintedChickenSoup();

        $app = Factory::officialAccount(config('easywechat.official_account.default'));

        foreach ($appids as $appid) {
            $app->template_message->send([
                'touser' => $appid,
                'template_id' => config('easywechat.official_account.default.template_id'),
//            'miniprogram' => [
//                'appid' => 'xxxxxxx',
//                'pagepath' => 'pages/xxx',
//            ],
                'data' => [
                    'weather' => $weather_info['weather'], // 天气
                    'temperature' => $weather_info['temperature'], // 温度
                    'day' => Carbon::parse('2021-06-30')->diffInDays(Carbon::now()), // 天
                    'text' => $tainted_chicken_soup, // 毒鸡汤
                ],
            ]);
        }

        Log::info('推送成功 ' . date('Y-m-d H:i:s'));
        return 0;
    }

    /**
     * 获取天气
     * @return array|string[]
     * @author: 陈志洪 <maxsihong@163.com>
     * @since: 2022/8/22
     * @see 高德api开放凭条：https://lbs.amap.com
     * @see 高德天气api：https://restapi.amap.com/v3/weather/weatherInfo?parameters
     */
    private function getWeather()
    {
        $param = [
            'key' => config('app.gd_token'),
            'city' => '440300', // 城市编码：深圳 https://lbs.amap.com/api/webservice/download
        ];

        $client = new \GuzzleHttp\Client();
        $response = $client->request('GET', 'https://restapi.amap.com/v3/weather/weatherInfo?' . http_build_query($param));

        $response = json_decode($response->getBody(), true);
        if ($response['status'] === 0) {
            return [
                'city' => '未知',
                'weather' => '未知',
                'temperature' => '位置',
            ];
        }

        $info = $response['lives'][0];
        return [
            'city' => $info['city'],
            'weather' => $info['weather'],
            'temperature' => $info['temperature'],
        ];
    }

    // 获取毒鸡汤
    private function getTaintedChickenSoup()
    {
        $client = new \GuzzleHttp\Client();
        $response = $client->request('GET', 'https://api.oick.cn/dutang/api.php');

        return json_decode($response->getBody(), true);
    }
}
