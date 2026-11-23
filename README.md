# h5ai

[![license][license-img]][github] [![web][web-img]][web] [![github][github-img]][github]

A modern HTTP web server index for Apache httpd, lighttpd, and nginx.

> **注意**：本项目是 [lrsjng/h5ai](https://github.com/lrsjng/h5ai) 的 fork 版本，主要专注于安全修复和维护更新。


## 关于本 Fork

本项目 fork 自 [lrsjng/h5ai](https://github.com/lrsjng/h5ai)，原项目是一个优秀的 HTTP Web 服务器索引工具。由于原项目已停止维护，存在多个安全漏洞，本 fork 版本致力于：

- 🔒 **安全修复**：修复原项目中存在的多个安全漏洞，包括：
  - HTTP Header 注入漏洞
  - 信息泄露漏洞
  - 路径遍历漏洞
  - XSS 跨站脚本漏洞
  - Session 安全问题
  - 时序攻击漏洞
- 🛠️ **持续维护**：持续更新依赖，修复新发现的安全问题
- 📦 **保持兼容**：在修复安全问题的同时，保持与原版本的功能兼容性

感谢原作者 [Lars Jung](https://larsjung.de) 和所有贡献者的出色工作。


## 与原项目的关系

- 本项目基于 lrsjng/h5ai 的代码进行安全修复和维护
- 功能上保持向后兼容，可以无缝替换原版本
- 欢迎向本项目提交 issue 和 pull request
- 如果您需要原项目的信息，请访问 [lrsjng/h5ai](https://github.com/lrsjng/h5ai)


## Important

* Do **not** install any files from the `src` folder, they need to be
  preprocessed to work correctly!
* Find a preprocessed package and detailed install instructions on the
  [project page][web].
* For bug reports and feature requests please use [issues][github-issues].


## Build

There are installation ready packages for the latest [releases][release] and
[dev builds][develop]. But to build **h5ai** yourself either `git clone` or
download the repository. From within the root folder run the following
commands to find a fresh zipball in folder `build` (tested on linux only,
requires [`node 10.0+`][node] to be installed, might work on other
configurations).

~~~sh
> npm install
> npm run build
~~~


## License

The MIT License (MIT)

Copyright (c) 2020 Lars Jung (https://larsjung.de)

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.


## References

**h5ai** profits from other projects, all of them licensed under the MIT license
too. Exceptions are some [Material Design icons][material-design-icons] (CC BY 4.0).


[web]: https://larsjung.de/h5ai/
[github]: https://github.com/lrsjng/h5ai
[github-issues]: https://github.com/lrsjng/h5ai/issues
[release]: https://release.larsjung.de/h5ai/
[develop]: https://release.larsjung.de/h5ai/develop/
[node]: https://nodejs.org
[material-design-icons]: https://github.com/google/material-design-icons

[license-img]: https://img.shields.io/badge/license-MIT-a0a060.svg?style=flat-square
[web-img]: https://img.shields.io/badge/web-larsjung.de/h5ai-a0a060.svg?style=flat-square
[github-img]: https://img.shields.io/badge/github-lrsjng/h5ai-a0a060.svg?style=flat-square
