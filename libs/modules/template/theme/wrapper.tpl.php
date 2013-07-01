<!doctype html> <!--[if lt IE 7]> <html class="no-js ie6 oldie" lang="en"> <![endif]-->
<!--[if IE 7]>    <html class="no-js ie7 oldie" lang="en"> <![endif]-->
<!--[if IE 8]>    <html class="no-js ie8 oldie" lang="en"> <![endif]-->
<!--[if gt IE 8]><!-->
<html class="no-js" lang="en" manifmest="<?php print $this->manifest_path?>">
    <!--<![endif]-->
    <head>
        <meta charset="utf-8">
        <!-- Use the .htaccess and remove these lines to avoid edge case issues.
        More info: h5bp.com/b/378 -->
        <link href="data:image/x-icon;base64,AAABAAEAEBAAAAAAAABoBQAAFgAAACgAAAAQAAAAIAAAAAEACAAAAAAAAAEAAAAAAAAAAAAAAAEAAAAAAAAAAAAA6+vrAKGhoQD///8A+Pj4ADg3NQCurq4A8PDwALq6ugD29vYA7+/vANTU1ADNzc0A4ODgAPT09ADZ2dkAo6OjAObm5gDExMQA8/PzAL29vQDl5eUAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAARCAAAAAAAAAAAAAAAAAARFAYAAAAAAAAAAAAAAAARFA8UBgAAAAARFAYGBgYUFA8KDRQGAAARFAwPDw8PDw0KCgoNFAYAFAwVCgoKCgoKCgoKCg8SFAYPExUVFRUVFRUVFRUNCwICDxMFBQULBQUFBQUFAQsCEA8JExMHDg4ODgoKCgELAgYPBBUVFRUVFRUVFRUNCwIGDwQFBQUFBQsFBQUFAQsCFAwVAwMDAwMDAwMTExUMFBEUDA8PDw8PDw8PDw8MFBEAERQGEBAQEBAQAgYGFBEAAAAAAAAAAAAAAAAAAAAAAP//AAD/zwAA/48AAP8HAACAAwAAAAEAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAgAEAAP//AAA=" rel="icon" type="image/x-icon" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <meta name="apple-mobile-web-app-capable" content="yes" />
        <title><?php print $this->title;?></title>
        <meta name="description" content="">
        <meta name="author" content="">
        <!-- Mobile viewport optimized: j.mp/bplateviewport -->
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <!-- Place favicon.ico and apple-touch-icon.png in the root directory: mathiasbynens.be/notes/touch-icons -->
        <!-- LESS -->
        <?php print $this->less_complied;?>
        <!-- end LESS -->
        <!-- CSS: implied media=all -->
        <!-- CSS concatenated and minified via ant build script-->
        <?php print $this->css;?>
        <!-- end CSS-->
        <!-- More ideas for your <head> here: h5bp.com/d/head-Tips -->
        <!-- All JavaScript at the bottom, except for Modernizr / Respond.
        Modernizr enables HTML5 elements & feature detects; Respond is a polyfill for min/max-width CSS3 Media Queries
        For optimal performance, use a custom Modernizr build: www.modernizr.com/download/ -->
        <style>
        	.infobox{
        		position:absolute;
        		bottom:0px;
        		right:0px;
        		border:1px solid #ccc;
        		width:400px;
        	}
        	
        </style>
    </head>
    <body data-spy="scroll" data-target=".subnav" data-offset="50">
        <div id="fb-root"></div>
        <!-- Navbar
        ================================================== -->
        <div class="navbar navbar-top">
            <div class="navbar-inner">
                <div class="container">
                    <?php print(l(config('SITE_NAME'), '/', 'brand'));?>
                    <div class="nav hidden-phone">
                        <?php print ($this->render_nav('top'))
                        ?>
                    </div>
                    <div class="nav hidden-phone pull-right">
                        <?php print($this->render_nav('top-right'))
                        ?>
                    </div>
                    <div class="nav visible-phone" id="phone-nav">
                        <?php print($this->render_nav('phone'))
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="container-fluid container-body">
            <div class="row-fluid">
                <div class="span12 body">
                    <div style="padding:10px">
                        <?php print($this->compiled_messages)
                        ?>
                        <?php 
                        if(isset($this->page_title)){
                            echo '<h1>', $this->page_title, '</h1>';
                        }
                        ?>
                        <?php print($this->content)
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <?php print $this->js;?>
        <script type="text/javascript">

          var _gaq = _gaq || [];
          _gaq.push(['_setAccount', 'UA-36947499-1']);
          _gaq.push(['_trackPageview']);

          (function() {
            var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
            ga.src = ('https:' == document.location.protocol ? 'https://' : 'http://') + 'stats.g.doubleclick.net/dc.js';
            var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
          })();

        </script>
    </body>
</html>
