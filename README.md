# Candy Cane for WordPress

Candy Cane for WordPress, was built using Foundation framework with the exceptional capabilities of [ZURB's Foundation Framework](http://foundation.zurb.com/) and [HTML5 Boilerplate](http://html5boilerplate.com/).

## Demonstration

You can view Candy Cane for WordPress online at this address: [http://demo.agustealo.com/wordpress](http://fwp.drewsymo.com)

You can view Foundation, for WordPress (FWP) online at this address: [http://fwp.drewsymo.com](http://fwp.drewsymo.com)
## Features

Candy Cane features everything ZURB's Foundation Framework and HTML5 Boilerplate have to offer.

* All your common WordPress template files
* Orbit for WordPress, ZURB's image and content slider tailored for WordPress, with the ability to manage your slider through WordPress
* A ySlow score of 99 (in regards to 'Small Site or Blog')
* Clean, validated code
* A little snippet that 'hides' the address bar on the iPhone
* An extremely awesome pagination script by @ericmartin, using Foundations pagination CSS
* An improved viewport snippet, allowing the same scale over horizontal and portrait orientations


### Orbit, for WordPress

Demonstration: [http://fwp.drewsymo.com/orbit](http://fwp.drewsymo.com/orbit)

Orbit, for WordPress, is Foundation's awesome slider built to work in WordPress. It allows you to manage your slider images and content through the backend. Neat, right? 

Just head into your admin panel, and:

* Click the link named 'Orbit' in the left hand side navigation
* Create a new post within that category
* Click 'Featured Image' on the right hand side
* Upload your image and click 'Set as featured image'
* Hit publish, and you're done!

```HTML
<h3>Orbit, for WordPress</h3>
	<div class="row">
		<div class="twelve columns">
			<div id="featured"> 
				<?php SliderContent(); ?>
			</div>
		</div>
	</div>
```

When using Orbit, it's best to upload an image to it as the height is inherited - that, or - change the min-height of Orbit within app.css (if your only using text), like so:

``` 
.orbit-wrapper {
	min-height:400px;
}
```

You can manage Orbit's options through app.js (don't worry, theres an options page coming shortly)

## Authors

**Agustealo Johnson**

+ Agustealo is a Hybrid Designer living in New York City.
+ Follow [Agustealo on Twitter](http://www.twitter.com/agustealo)
+ Follow [Agustealo on FaceBook](https://www.facebook.com/pages/Agustealo/183441298351569?ref=hl)
+ Follow [Agustealo on Google +](https://plus.google.com/u/0/+AgustealoJohnson)
+ Blog [Agustealo's Website](http://www.agustealo.com)
+ Dev's [Agustealo's Website](agustealo.github.io)

**ZURB**

+ Foundation was made by ZURB, an interaction design and design strategy firm in Campbell, CA.
+ Follow [ZURB on Twitter](http://twitter.com/#!/foundationzurb)

**Drew Morris**

+ Drew Morris is a freelance Website Designer currently living in Sydney, Australia.
+ Follow [Drew on Twitter](http://www.twitter.com/drewsymo)
+ Follow [Drew on Google +](https://plus.google.com/114153589610660530694?rel=author)
+ View [Drew's Website](http://www.drewsymo.com)

## License

### Candy Cane, for WordPress

Candy Cane is listed under Public Domain.

### Foundation, for WordPress

Foundation, for WordPress, is listed under Public Domain.
