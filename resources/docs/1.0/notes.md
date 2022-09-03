# 笔记

---

- [php 保存emoji表情方案](#emoji-title)
  - [1. 使用utf8mb4字符集](#emoji-1)
  - [2. 编码解码](#emoji-2)
  - [3. 使用base64编码](#emoji-3)
  - [4. 去掉emoji表情](#emoji-4)

<a name="emoji-title"></a>
## php 保存emoji表情方案

> {primary} 随着手机的普及，现在移动开发很火爆，已经远远超过了pc端。
 在移动设备经常会发生用户发送的内容中包含emoji表情，在显示时就是乱码。
 一般是因为Mysql表设计时，都是用UTF8字符集的。把带有emoji的昵称字段往里面insert一下就没了，整个字段变成了空字符串。
 这是因为Mysql的utf8字符集是3字节的，而emoji是4字节，这样整个昵称就无法存储了。

<a name="emoji-1"></a>
### 1. 使用utf8mb4字符集
    如果你的mysql版本>=5.5.3，你大可直接将utf8直接升级为utf8mb4字符集
这种4字节的utf8编码可完美兼容旧的3字节utf8字符集，并且可以直接存储emoji表情，是最好的解决方案
至于字节增大带来的性能损耗，我看过一些评测，几乎是可以忽略不计的。
 
具体操作如下：
    1. mysql的版本必须为v5.5.3或更高
    2. 把数据库的编码改成utf8mb4 -- UTF-8 Unicode
    3. 然后需要存储emoji表情的字段选择utf8mb4_general_ci
    4. 数据库连接也需要改为utf8mb4
    5. tp5修改配置文件，config.php：
    ``` php
    'charset'   => Env::get('database.charset', 'utf8mb4'),
    ```

<a name="emoji-2"></a>
### 2. 编码解码：不修改数据库，表，字段类型的方案
```php
    /**
     * 把用户输入的文本转义（主要针对特殊符号和emoji表情）
     * @param string $str 参数
     * @return string
     */
    public static function userTextEncode(string $str): string
    {
        if (empty($str)) {
            return '';
        }

        $text = json_encode($str); //暴露出unicode
        $text = preg_replace_callback("/(\\\u[ed][0-9a-f]{3})/i", function ($str) {
            return addslashes($str[0]);
        }, $text); //将emoji的unicode留下，其他不动，这里的正则比原答案增加了d，因为我发现我很多emoji实际上是\ud开头的，反而暂时没发现有\ue开头。
        return json_decode($text);
    }

    /**
     * 解码上面的转义
     * @param string $str 参数
     * @return string
     */
    public static function userTextDecode(string $str): string
    {
        $text = json_encode($str); //暴露出unicode
        $text = preg_replace_callback('/\\\\\\\\/i', function ($str) {
            return '\\';
        }, $text); //将两条斜杠变成一条，其他不动
        return json_decode($text);
    }
```

<a name="emoji-3"></a>
### 3. 使用base64编码
    如果你因为某些原因无法使用utf8mb4的话，你还可以使用base64来曲线救国
使用例如base64_encode之类的函数编码过后的emoji可以直接存储在utf8字节集的数据表中，取出时base64_decode一下即可

<a name="emoji-4"></a>
### 4. 去掉emoji表情
    在iOS以外的平台上，例如PC或者android。如果你需要显示emoji，就得准备一大堆emoji图片并使用第三方前端类库才行。
emoji表情是个麻烦的东西，即使你能存储，也不一定能完美显示，所以我们可以将它过滤掉。
在google里找到能用的过滤的代码，如下
```php
    /**
     * 过滤特殊字符
     * @param string $str 参数
     * @return string
     */
    public function filter(string $str): string
    {
        if (empty($str)) {
            return '';
        }

        $str = preg_replace('/\xEE[\x80-\xBF][\x80-\xBF]|\xEF[\x81-\x83][\x80-\xBF]/', '', $str);
        $str = preg_replace('/xE0[x80-x9F][x80-xBF]‘.‘|xED[xA0-xBF][x80-xBF]/S', '?', $str);
        return @json_decode(preg_replace("#(\\\ud[0-9a-f]{3})#ie", "", json_encode($str)));
    }
```
