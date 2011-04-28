Name: geshi 
Summary: Generic Syntax Highlighter
Version: 1.0.8.4
Release: 0 
Group: Development/Tools
License: GPL
URL: http://qbnz.com/highlighter/index.php 
Source0: %{name}-%{version}.tar.bz2
Patch1: geshi.patch
BuildArch: noarch
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root
Requires:  php
Packager: Guillaume Storchi <guillaume.storchi@xrce.xerox.com>

%description
GeSHi is exactly what the acronym stands for: a Generic Syntax Highlighter. As long as you have a language file for almost any computer language - whether it be a scripting language, object orientated, markup or anything in between - GeSHi can highlight it! GeSHi is extremely customisable - the same source can be highlighted multiple times in multiple ways - the same source even with a different language. GeSHi outputs XHTML strict compliant code1, and can make use of CSS to save on the amount of output. And what is the cost for all of this? You need PHP. That’s all!
%prep
%setup -n %{name}
%patch1 -p1 
%build

%install
%{__install} -d -m0755 %{buildroot}/%{_datadir}/%{name}
%{__cp} -ar * %{buildroot}/%{_datadir}/%{name}
%clean
%{__rm} -rf %{buildroot} 

%files
%defattr(-, root, root, 0755)

%{_datadir}/geshi/
%{_datadir}/geshi/contrib/
%{_datadir}/geshi/contrib/aliased.php
%{_datadir}/geshi/contrib/cssgen.php
%{_datadir}/geshi/contrib/cssgen2.php
%{_datadir}/geshi/contrib/example.php
%{_datadir}/geshi/contrib/langcheck.php
%{_datadir}/geshi/docs/
%{_datadir}/geshi/docs/BUGS
%{_datadir}/geshi/docs/CHANGES
%{_datadir}/geshi/docs/COPYING
%{_datadir}/geshi/docs/README
%{_datadir}/geshi/docs/THANKS
%{_datadir}/geshi/docs/TODO
%{_datadir}/geshi/docs/api/
%{_datadir}/geshi/docs/api/__filesource/
%{_datadir}/geshi/docs/api/__filesource/fsource_geshi_core_geshi.php.html
%{_datadir}/geshi/docs/api/blank.html
%{_datadir}/geshi/docs/api/classtrees_geshi.html
%{_datadir}/geshi/docs/api/elementindex.html
%{_datadir}/geshi/docs/api/elementindex_geshi.html
%{_datadir}/geshi/docs/api/errors.html
%{_datadir}/geshi/docs/api/geshi/
%{_datadir}/geshi/docs/api/geshi/core/
%{_datadir}/geshi/docs/api/geshi/core/GeSHi.html
%{_datadir}/geshi/docs/api/geshi/core/_geshi.php.html
%{_datadir}/geshi/docs/api/index.html
%{_datadir}/geshi/docs/api/li_geshi.html
%{_datadir}/geshi/docs/api/media/
%{_datadir}/geshi/docs/api/media/banner.css
%{_datadir}/geshi/docs/api/media/images/
%{_datadir}/geshi/docs/api/media/images/AbstractClass.png
%{_datadir}/geshi/docs/api/media/images/AbstractClass_logo.png
%{_datadir}/geshi/docs/api/media/images/AbstractMethod.png
%{_datadir}/geshi/docs/api/media/images/AbstractPrivateClass.png
%{_datadir}/geshi/docs/api/media/images/AbstractPrivateClass_logo.png
%{_datadir}/geshi/docs/api/media/images/AbstractPrivateMethod.png
%{_datadir}/geshi/docs/api/media/images/Class.png
%{_datadir}/geshi/docs/api/media/images/Class_logo.png
%{_datadir}/geshi/docs/api/media/images/Constant.png
%{_datadir}/geshi/docs/api/media/images/Constructor.png
%{_datadir}/geshi/docs/api/media/images/Destructor.png
%{_datadir}/geshi/docs/api/media/images/Function.png
%{_datadir}/geshi/docs/api/media/images/Global.png
%{_datadir}/geshi/docs/api/media/images/I.png
%{_datadir}/geshi/docs/api/media/images/Index.png
%{_datadir}/geshi/docs/api/media/images/Interface.png
%{_datadir}/geshi/docs/api/media/images/Interface_logo.png
%{_datadir}/geshi/docs/api/media/images/L.png
%{_datadir}/geshi/docs/api/media/images/Lminus.png
%{_datadir}/geshi/docs/api/media/images/Lplus.png
%{_datadir}/geshi/docs/api/media/images/Method.png
%{_datadir}/geshi/docs/api/media/images/Page.png
%{_datadir}/geshi/docs/api/media/images/Page_logo.png
%{_datadir}/geshi/docs/api/media/images/PrivateClass.png
%{_datadir}/geshi/docs/api/media/images/PrivateClass_logo.png
%{_datadir}/geshi/docs/api/media/images/PrivateMethod.png
%{_datadir}/geshi/docs/api/media/images/PrivateVariable.png
%{_datadir}/geshi/docs/api/media/images/StaticMethod.png
%{_datadir}/geshi/docs/api/media/images/StaticVariable.png
%{_datadir}/geshi/docs/api/media/images/T.png
%{_datadir}/geshi/docs/api/media/images/Tminus.png
%{_datadir}/geshi/docs/api/media/images/Tplus.png
%{_datadir}/geshi/docs/api/media/images/Variable.png
%{_datadir}/geshi/docs/api/media/images/blank.png
%{_datadir}/geshi/docs/api/media/images/class_folder.png
%{_datadir}/geshi/docs/api/media/images/empty.png
%{_datadir}/geshi/docs/api/media/images/file.png
%{_datadir}/geshi/docs/api/media/images/folder.png
%{_datadir}/geshi/docs/api/media/images/function_folder.png
%{_datadir}/geshi/docs/api/media/images/next_button.png
%{_datadir}/geshi/docs/api/media/images/next_button_disabled.png
%{_datadir}/geshi/docs/api/media/images/package.png
%{_datadir}/geshi/docs/api/media/images/package_folder.png
%{_datadir}/geshi/docs/api/media/images/previous_button.png
%{_datadir}/geshi/docs/api/media/images/previous_button_disabled.png
%{_datadir}/geshi/docs/api/media/images/private_class_logo.png
%{_datadir}/geshi/docs/api/media/images/tutorial.png
%{_datadir}/geshi/docs/api/media/images/tutorial_folder.png
%{_datadir}/geshi/docs/api/media/images/up_button.png
%{_datadir}/geshi/docs/api/media/stylesheet.css
%{_datadir}/geshi/docs/api/packages.html
%{_datadir}/geshi/docs/api/todolist.html
%{_datadir}/geshi/docs/geshi-doc.html
%{_datadir}/geshi/docs/geshi-doc.txt
%{_datadir}/geshi/docs/phpdoc.ini
%{_datadir}/geshi/geshi/
%{_datadir}/geshi/geshi/abap.php
%{_datadir}/geshi/geshi/actionscript.php
%{_datadir}/geshi/geshi/actionscript3.php
%{_datadir}/geshi/geshi/ada.php
%{_datadir}/geshi/geshi/apache.php
%{_datadir}/geshi/geshi/applescript.php
%{_datadir}/geshi/geshi/apt_sources.php
%{_datadir}/geshi/geshi/asm.php
%{_datadir}/geshi/geshi/asp.php
%{_datadir}/geshi/geshi/autoit.php
%{_datadir}/geshi/geshi/avisynth.php
%{_datadir}/geshi/geshi/bash.php
%{_datadir}/geshi/geshi/basic4gl.php
%{_datadir}/geshi/geshi/bf.php
%{_datadir}/geshi/geshi/bibtex.php
%{_datadir}/geshi/geshi/blitzbasic.php
%{_datadir}/geshi/geshi/bnf.php
%{_datadir}/geshi/geshi/boo.php
%{_datadir}/geshi/geshi/c.php
%{_datadir}/geshi/geshi/c_mac.php
%{_datadir}/geshi/geshi/caddcl.php
%{_datadir}/geshi/geshi/cadlisp.php
%{_datadir}/geshi/geshi/cfdg.php
%{_datadir}/geshi/geshi/cfm.php
%{_datadir}/geshi/geshi/cil.php
%{_datadir}/geshi/geshi/cmake.php
%{_datadir}/geshi/geshi/cobol.php
%{_datadir}/geshi/geshi/cpp-qt.php
%{_datadir}/geshi/geshi/cpp.php
%{_datadir}/geshi/geshi/csharp.php
%{_datadir}/geshi/geshi/css.php
%{_datadir}/geshi/geshi/d.php
%{_datadir}/geshi/geshi/dcs.php
%{_datadir}/geshi/geshi/delphi.php
%{_datadir}/geshi/geshi/diff.php
%{_datadir}/geshi/geshi/div.php
%{_datadir}/geshi/geshi/dos.php
%{_datadir}/geshi/geshi/dot.php
%{_datadir}/geshi/geshi/eiffel.php
%{_datadir}/geshi/geshi/email.php
%{_datadir}/geshi/geshi/erlang.php
%{_datadir}/geshi/geshi/fo.php
%{_datadir}/geshi/geshi/fortran.php
%{_datadir}/geshi/geshi/freebasic.php
%{_datadir}/geshi/geshi/genero.php
%{_datadir}/geshi/geshi/gettext.php
%{_datadir}/geshi/geshi/glsl.php
%{_datadir}/geshi/geshi/gml.php
%{_datadir}/geshi/geshi/gnuplot.php
%{_datadir}/geshi/geshi/groovy.php
%{_datadir}/geshi/geshi/haskell.php
%{_datadir}/geshi/geshi/hq9plus.php
%{_datadir}/geshi/geshi/html4strict.php
%{_datadir}/geshi/geshi/idl.php
%{_datadir}/geshi/geshi/ini.php
%{_datadir}/geshi/geshi/inno.php
%{_datadir}/geshi/geshi/intercal.php
%{_datadir}/geshi/geshi/io.php
%{_datadir}/geshi/geshi/java.php
%{_datadir}/geshi/geshi/java5.php
%{_datadir}/geshi/geshi/javascript.php
%{_datadir}/geshi/geshi/kixtart.php
%{_datadir}/geshi/geshi/klonec.php
%{_datadir}/geshi/geshi/klonecpp.php
%{_datadir}/geshi/geshi/latex.php
%{_datadir}/geshi/geshi/lisp.php
%{_datadir}/geshi/geshi/locobasic.php
%{_datadir}/geshi/geshi/lolcode.php
%{_datadir}/geshi/geshi/lotusformulas.php
%{_datadir}/geshi/geshi/lotusscript.php
%{_datadir}/geshi/geshi/lscript.php
%{_datadir}/geshi/geshi/lsl2.php
%{_datadir}/geshi/geshi/lua.php
%{_datadir}/geshi/geshi/m68k.php
%{_datadir}/geshi/geshi/make.php
%{_datadir}/geshi/geshi/matlab.php
%{_datadir}/geshi/geshi/mirc.php
%{_datadir}/geshi/geshi/modula3.php
%{_datadir}/geshi/geshi/mpasm.php
%{_datadir}/geshi/geshi/mxml.php
%{_datadir}/geshi/geshi/mysql.php
%{_datadir}/geshi/geshi/nsis.php
%{_datadir}/geshi/geshi/oberon2.php
%{_datadir}/geshi/geshi/objc.php
%{_datadir}/geshi/geshi/ocaml-brief.php
%{_datadir}/geshi/geshi/ocaml.php
%{_datadir}/geshi/geshi/oobas.php
%{_datadir}/geshi/geshi/oracle11.php
%{_datadir}/geshi/geshi/oracle8.php
%{_datadir}/geshi/geshi/pascal.php
%{_datadir}/geshi/geshi/per.php
%{_datadir}/geshi/geshi/perl.php
%{_datadir}/geshi/geshi/php-brief.php
%{_datadir}/geshi/geshi/php.php
%{_datadir}/geshi/geshi/pic16.php
%{_datadir}/geshi/geshi/pixelbender.php
%{_datadir}/geshi/geshi/plsql.php
%{_datadir}/geshi/geshi/povray.php
%{_datadir}/geshi/geshi/powershell.php
%{_datadir}/geshi/geshi/progress.php
%{_datadir}/geshi/geshi/prolog.php
%{_datadir}/geshi/geshi/properties.php
%{_datadir}/geshi/geshi/providex.php
%{_datadir}/geshi/geshi/python.php
%{_datadir}/geshi/geshi/qbasic.php
%{_datadir}/geshi/geshi/rails.php
%{_datadir}/geshi/geshi/rebol.php
%{_datadir}/geshi/geshi/reg.php
%{_datadir}/geshi/geshi/robots.php
%{_datadir}/geshi/geshi/ruby.php
%{_datadir}/geshi/geshi/sas.php
%{_datadir}/geshi/geshi/scala.php
%{_datadir}/geshi/geshi/scheme.php
%{_datadir}/geshi/geshi/scilab.php
%{_datadir}/geshi/geshi/sdlbasic.php
%{_datadir}/geshi/geshi/smalltalk.php
%{_datadir}/geshi/geshi/smarty.php
%{_datadir}/geshi/geshi/sql.php
%{_datadir}/geshi/geshi/tcl.php
%{_datadir}/geshi/geshi/teraterm.php
%{_datadir}/geshi/geshi/text.php
%{_datadir}/geshi/geshi/thinbasic.php
%{_datadir}/geshi/geshi/tsql.php
%{_datadir}/geshi/geshi/typoscript.php
%{_datadir}/geshi/geshi/vb.php
%{_datadir}/geshi/geshi/vbnet.php
%{_datadir}/geshi/geshi/verilog.php
%{_datadir}/geshi/geshi/vhdl.php
%{_datadir}/geshi/geshi/vim.php
%{_datadir}/geshi/geshi/visualfoxpro.php
%{_datadir}/geshi/geshi/visualprolog.php
%{_datadir}/geshi/geshi/whitespace.php
%{_datadir}/geshi/geshi/whois.php
%{_datadir}/geshi/geshi/winbatch.php
%{_datadir}/geshi/geshi/xml.php
%{_datadir}/geshi/geshi/xorg_conf.php
%{_datadir}/geshi/geshi/xpp.php
%{_datadir}/geshi/geshi/z80.php
%{_datadir}/geshi/geshi.php

%changelog
* Fri Dec 11 2009 Guillaume Storchi <guillaume.storchi@xrce.xerox.com>
- initial build
