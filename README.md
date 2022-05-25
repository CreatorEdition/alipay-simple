# Alipay easysdk for php
60秒接入支付宝支付，一个PHP文件即可服用  
自己在集成的时候踩了太多官方的坑了 = =

## 主要目的

- [x] 降低 PHP 依赖至 5.0。
- [x] 兼容 PHP 7.x。
- [x] 兼容 PHP 8.0+  ( 8.0 , 8.1 )。
- [x] 移除官方 API 文档内 `已弃用` 特性。
- [x] 移除难以拓展的调试、日志等特性，以便于集成第三方框架和扩展包。
- [x] 移除编码转换特性，统一使用 `UTF-8`。
- [ ] 其它优化，持续进行中 ...


## 文件对于及说明

| 文件         | 说明     |
|:-----------|:-------|
| pc.php     | 电脑网站支付 | 
| return.php | 同步回调通知 |
| notify.php | 异步回调通知 |
| query.php  | 交易查询   |

## 其它资源

- [支付宝开放平台 - API 文档](https://docs.open.alipay.com/api/)
- [支付宝开放平台 - 开发者社区](https://openclub.alipay.com/index.php)