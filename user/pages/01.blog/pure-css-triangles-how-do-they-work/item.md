---
title: Pure CSS Triangles: How Do They Work?
date: 19:09 08/11/2013 
taxonomy:
    category: blog
    tag: [css, front-end]
---

I’ve been using static images for arrows for quite some time, and as you can imagine it’s been cumbersome. You can’t modify an image triangle in any way (at least by conventional means), so as the UI gets more complex, it gets increasingly difficult to maintain them. Not to mention that they eat up a little bit of your users’ bandwidth. A very small portion, perhaps, but still.

Let me give you an example. On a website that features 4 different colors of arrows meant to be displayed in 4 different places (top, right, bottom, left) of the elements they’re placed upon, you would need to generate 16 different triangles and add them in your sprite. Now, what if your designer decides to add 2 more colors and 4 more directions (top left, top right, bottom right, bottom left)? It’s a pain, right?

Fortunately, there’s a maintainable, cross-browser (even in IE6!), pure CSS way of achieving this: borders. You may have already seen it being used on many websites, like [The Verge](http://www.theverge.com/), [Gizmodo](http://gizmodo.com/), [Twitter](https://twitter.com/), even [Google](http://www.google.com/), but it first caught my eye on Google. At first it blew my mind and I couldn’t understand how it worked, but then it dawned on me. If it boggles (or did, at some point) your mind as well, continue reading.

<p data-height="250" data-theme-id="0" data-slug-hash="dGwDc" data-user="kojiroh" data-default-tab="result" class='codepen'>See the Pen <a href='http://codepen.io/kojiroh/pen/dGwDc'>dGwDc</a> by kojiroh (<a href='http://codepen.io/kojiroh'>@kojiroh</a>) on <a href='http://codepen.io'>CodePen</a></p>

But this costs us the ability to specify different borders for different edges of the element. To achieve this, we need to use the expanded form (we can still use shorthands for the quad rules, as seen in the following example). Let’s try it.

<p data-height="250" data-theme-id="0" data-slug-hash="dbiFa" data-user="kojiroh" data-default-tab="result" class='codepen'>See the Pen <a href='http://codepen.io/kojiroh/pen/dbiFa'>dbiFa</a> by kojiroh (<a href='http://codepen.io/kojiroh'>@kojiroh</a>) on <a href='http://codepen.io'>CodePen</a></p>

Using different colors for different edges reveals another great feature. If you look closely, you can see that intersecting edges converge into a corner, rather than overlap on each other. See the following example for a closer look.

<p data-height="250" data-theme-id="0" data-slug-hash="BbCKG" data-user="kojiroh" data-default-tab="result" class='codepen'>See the Pen <a href='http://codepen.io/kojiroh/pen/BbCKG'>BbCKG</a> by kojiroh (<a href='http://codepen.io/kojiroh'>@kojiroh</a>) on <a href='http://codepen.io'>CodePen</a></p>

See those blue trapezoids? They’re formed by this very feature. The only reason we got trapezoids instead of triangles is that the text inside the div gave it a height. If we remove the text, we should get two blue isosceles triangles on each side.

<p data-height="250" data-theme-id="0" data-slug-hash="ivmsD" data-user="kojiroh" data-default-tab="result" class='codepen'>See the Pen <a href='http://codepen.io/kojiroh/pen/ivmsD'>ivmsD</a> by kojiroh (<a href='http://codepen.io/kojiroh'>@kojiroh</a>) on <a href='http://codepen.io'>CodePen</a></p>

We only want the triangles, so we need to remove those red parts. Let’s see what happens when we set the top and bottom border width to 0 pixels.

<p data-height="250" data-theme-id="0" data-slug-hash="kebat" data-user="kojiroh" data-default-tab="result" class='codepen'>See the Pen <a href='http://codepen.io/kojiroh/pen/kebat'>kebat</a> by kojiroh (<a href='http://codepen.io/kojiroh'>@kojiroh</a>) on <a href='http://codepen.io'>CodePen</a></p>

As you can see (in fact, can’t see), the element has completely disappeared because it’s 0 pixels tall. The previous example only worked because the vertical borders actually gave the div a height of 40 pixels. Let’s put the text back in and see what happens.

<p data-height="250" data-theme-id="0" data-slug-hash="qGiza" data-user="kojiroh" data-default-tab="result" class='codepen'>See the Pen <a href='http://codepen.io/kojiroh/pen/qGiza'>qGiza</a> by kojiroh (<a href='http://codepen.io/kojiroh'>@kojiroh</a>) on <a href='http://codepen.io'>CodePen</a></p>

This time the div and the blue borders did appear, but there’s no other borders pushing them, so they’ve stayed in a rectangular shape. We can’t put any text inside and we can’t set the widths of the borders to zero, and we can’t set an arbitrary height on our element either because that’s not really meaningful, besides it wouldn’t get us the triangles anyway. How do we get those triangles, then?

The color “transparent” comes to our aid here. Using a transparent color for the vertical borders allows us to give them each a width, and those widths give our element the height in order for it to be rendered.

<p data-height="250" data-theme-id="0" data-slug-hash="jvFBw" data-user="kojiroh" data-default-tab="result" class='codepen'>See the Pen <a href='http://codepen.io/kojiroh/pen/jvFBw'>jvFBw</a> by kojiroh (<a href='http://codepen.io/kojiroh'>@kojiroh</a>) on <a href='http://codepen.io'>CodePen</a></p>

Here they are, two perfect triangles! We only want one triangle, so we can just set the color of the border that corresponds to it to transparent, just like we did for the borders on the top and bottom.

<p data-height="250" data-theme-id="0" data-slug-hash="coALe" data-user="kojiroh" data-default-tab="result" class='codepen'>See the Pen <a href='http://codepen.io/kojiroh/pen/coALe'>coALe</a> by kojiroh (<a href='http://codepen.io/kojiroh'>@kojiroh</a>) on <a href='http://codepen.io'>CodePen</a></p>

We’ve been using the div element up until now, and as you know, the div element is a block level element. But we’re most likely going to use these triangles next to some other elements, like a speech bubble or a tooltip, so we’re going to need to use an inline or an inline-block element. Let’s go with a good old span. We still don’t want any text inside, so let’s keep its inside empty as well.

<p data-height="250" data-theme-id="0" data-slug-hash="mzLGA" data-user="kojiroh" data-default-tab="result" class='codepen'>See the Pen <a href='http://codepen.io/kojiroh/pen/mzLGA'>mzLGA</a> by kojiroh (<a href='http://codepen.io/kojiroh'>@kojiroh</a>) on <a href='http://codepen.io'>CodePen</a></p>

Now, that’s a weird look. In modern browsers, our triangle turns back into a trapezoid, and in IE<8 it doesn’t even appear at all. The reason for the first issue is, that, when attempting to calculate the height of an inline or inline-block element (unless specified explicitly), browsers use the height of the line they’re on. In our case, that’s a height of 16 pixels. IE<8, on the other hand, fails to even display the element because it needs a ```hasLayout``` trigger before it actually gets to calculate the height. Setting the zoom to 1 do get it to display, however, it doesn’t remove the unnecessary height.

Fortunately, though, we have a cross browser fix which not only removes the height, but also triggers hasLayout in IE<8: setting the line height to 0 pixels. Let’s give it a go, and while we’re at it, let’s give it a vertical-align: middle so it stays aligned with the text next to it.

<p data-height="250" data-theme-id="0" data-slug-hash="HGKnu" data-user="kojiroh" data-default-tab="result" class='codepen'>See the Pen <a href='http://codepen.io/kojiroh/pen/HGKnu'>HGKnu</a> by kojiroh (<a href='http://codepen.io/kojiroh'>@kojiroh</a>) on <a href='http://codepen.io'>CodePen</a></p>

Great! Now, let’s specify different lengths and colors for each side and see what happens. This will allow us to understand the inner workings of borders better.

<p data-height="300" data-theme-id="0" data-slug-hash="IxeaA" data-user="kojiroh" data-default-tab="result" class='codepen'>See the Pen <a href='http://codepen.io/kojiroh/pen/IxeaA'>IxeaA</a> by kojiroh (<a href='http://codepen.io/kojiroh'>@kojiroh</a>) on <a href='http://codepen.io'>CodePen</a></p>

Neat. If we examine the heights of each triangle, we can see that the width of the borders on each side correspond to the height of the triangle that lands on that side.

![Border heights in action](images/border-height.jpg)

With this knowledge, we can easily create other arrows facing each of the 8 directions now.

<p data-height="300" data-theme-id="0" data-slug-hash="KpJcf" data-user="kojiroh" data-default-tab="result" class='codepen'>See the Pen <a href='http://codepen.io/kojiroh/pen/KpJcf'>KpJcf</a> by kojiroh (<a href='http://codepen.io/kojiroh'>@kojiroh</a>) on <a href='http://codepen.io'>CodePen</a></p>

There they are. We can now use them anywhere we want.

All the triangles we’ve created so far have been right triangles, but in some cases we may need an acute triangle. Let’s put to use what we’ve discovered on the ```CMYK``` triangle fan, and mess up with the sizes of our borders.

<p data-height="300" data-theme-id="0" data-slug-hash="mKhet" data-user="kojiroh" data-default-tab="result" class='codepen'>See the Pen <a href='http://codepen.io/kojiroh/pen/mKhet'>mKhet</a> by kojiroh (<a href='http://codepen.io/kojiroh'>@kojiroh</a>) on <a href='http://codepen.io'>CodePen</a></p>

There we go, four weird angled triangles. We can now safely say that any right or acute triangle is possible as long as one edge is perfectly straight, either horizontally or vertically.

And now that we know how those triangles work, we can put our new-found knowledge to use and actually create something meaningful. For example, a menu with multiple levels and triangles indicating that an item has children which also change when the item is expanded. While we’re at it, let’s use an ```<i>``` tag, because in this case our arrows are actually icons, and using an ```<i>``` tag is more semantically correct than using a span, for example. Let’s also use SASS/SCSS because it’s much, much easier to read and maintain. Don’t worry if you’re not familiar with ```SASS```, as you can always view the compiled/final CSS on Codepen by clicking on the “Edit on Codepen” link on the top right corner of the frame below.

<p data-height="300" data-theme-id="0" data-slug-hash="lAeck" data-user="kojiroh" data-default-tab="result" class='codepen'>See the Pen <a href='http://codepen.io/kojiroh/pen/lAeck'>Pure CSS Multi-Leveled Horizontal Menu</a> by kojiroh (<a href='http://codepen.io/kojiroh'>@kojiroh</a>) on <a href='http://codepen.io'>CodePen</a></p>

Or a collapsible vertical menu.

<p data-height="300" data-theme-id="0" data-slug-hash="foAFb" data-user="kojiroh" data-default-tab="result" class='codepen'>See the Pen <a href='http://codepen.io/kojiroh/pen/foAFb'>Multi-Leveled Vertical Collapsible Menu</a> by kojiroh (<a href='http://codepen.io/kojiroh'>@kojiroh</a>) on <a href='http://codepen.io'>CodePen</a></p>

Or a speech bubble (and kittens for your pleasure).

<p data-height="300" data-theme-id="0" data-slug-hash="qylpz" data-user="kojiroh" data-default-tab="result" class='codepen'>See the Pen <a href='http://codepen.io/kojiroh/pen/qylpz'>qylpz</a> by kojiroh (<a href='http://codepen.io/kojiroh'>@kojiroh</a>) on <a href='http://codepen.io'>CodePen</a></p>

Well, that’s it from me, I assume you’ve already got the basics. What’s beyond this point is entirely up to you, your needs and your imagination. 

<script async src="http://codepen.io/assets/embed/ei.js"></script>
