# IndieWeb integration for Drupal 8

## About this module.

Integrates the philosophy of IndieWeb in your Drupal website.
For more information about IndieWeb, see https://indieweb.org/.

Current functionality:

- Send and receive webmentions and pingbacks via Webmention.io
- Syndicate content, likes etc via [brid.gy](https://brid.gy), store syndications
- Microformats for content and images
- Allow users to login and create accounts
- IndieAuth for Authentication API
- Create content via Micropub endpoint
  (note, article, event, rsvp, reply, like, repost, bookmark)
- Auto-create comments from 'in-reply-to'
- Reply on comments and send webmention
- Feeds: microformats, atom and jf2
- Send a micropub post to Aperture on incoming webmentions
- Microsub link exposing
- Fediverse integration

More information is in this README and on the help pages.

Development happens on github: https://github.com/swentel/indieweb

Releases are available on drupal.org: https://www.drupal.org/project/indieweb

## IndieWebify.me / sturdy-backbone.glitch.me / xray.p3k.io

Use https://indiewebify.me/ to perform initial checks to see if your site is Indieweb ready. It can scan for certain
markup after you've done the configuration with this module (and optionally more yourself).
Note that author discovery doesn't fully work 100% on IndieWebify for posts, use https://sturdy-backbone.glitch.me.
Another good tool is http://xray.p3k.io, which displays the results in JSON.

## To install

- composer require indieweb/mention-client
- composer require p3k/xray
- composer require p3k/Micropub
- go to admin/modules and toggle 'IndieWeb' to enable the module.

## Webmentions / Webmention.io

Webmention.io is a hosted service created to easily handle webmentions (and legacy pingbacks) on any web page. The
module exposes an endpoint (/webmention/notify) to receive pingbacks and webmentions via this service. Pingbacks are
also validated to make sure that the source URL has a valid link to the target. Webmention.io is open source so you can
also host the service yourself.

You need an account for receiving the webhooks at https://webmention.io. As soon as one webmention is recorded at that
service, you can set the webhook to http://your_domain/webmention/notify and enter a secret. Pingbacks can be done
without an account, but you probably want both right :)

Pingbacks and webmentions are stored in a simple entity type called webmentions as user 1. An overview of collected
links is available at admin/content/webmentions.

To create an account, you need to authenticate with https://indieauth.com/ which requires you to add the "rel=me"
attribute on links to your social accounts. See https://indieauth.com/setup for full instructions. See also Indieauth
further below.

- Configuration is at /admin/config/services/indieweb/webmention
- Overview of all collected webmentions and pingbacks are at /admin/content/webmention

**Blocks**

- Webmentions: render like and repost webmentions per page
- Webmention notify form: let people submit a URL if the current page is mentioned there
- RSVP: shows people attending, interested for an event
- Pingbacks: render all urls to site pinging back per page

**Theming**

Webmentions are rendered through templates. Suggestions are available per property (e.g. webmention--like-of.tpl.php).

![ScreenShot](https://realize.be/sites/default/files/2018-03/webmention-basic.png)

## Sending webmentions and syndicating content with Bridgy

Syndicating and sending webmentions can be done per node in the "Publish to" fieldset, which is protected with the 
"send webmentions" permission.

For syndicating, a checkbox will be available on the node form for sending your content per target (e.g. twitter,
etc). There is also a syndication field available to render your syndications for POSSE-Post-Discovery, see
https://indieweb.org/posse-post-discovery. The full list of syndications is available at admin/content/syndication.
When you toggle to syndicate, an entry is created in the queue which you can either handle with drush or by cron. This
will send a webmention to bridgy for instance.

The drush command is 'indieweb-send-webmentions'

Bridgy pulls comments, likes, and reshares on social networks back to your web site. You can also use it to post to
social networks - or comment, like, reshare, or even RSVP - from your own web site. Bridgy is open source so you can
also host the service yourself. To receive content from those networks, bridgy will send a webmention, so you only need 
to enable the webmention endpoint.

Your content needs to have proper microformat classes on your content, images etc and following snippet needs to
available on the page you want to publish, e.g.

  ```
  <a href="https://brid.gy/publish/twitter"></a>
  ```

Note that Bridgy prefers p-summary over e-content, but for original tweets p-name first. See
https://brid.gy/about#microformats. You can preview your posts on Bridgy to verify your markup is ok.

The module ships with a default Twitter target. More targets and other configuration can be configured at
/admin/config/services/indieweb/send. These targets are also used for the q=syndicate-to request for micropub, see
micropub for more information.

This module exposes per target an extra field which you can configure on the 'Manage Display' pages of each node and
comment type. That field exposes that snippet. See indieweb_node_view() and indieweb_comment_view(). More info about
this at https://brid.gy/about#webmentions. Currently this field will be printed, even if you do not syndicate to that
target, that will be altered later.

You can also configure to just enter a custom URL, or use a "link" field to send a webmention to. On comments, these 
link fields can be pre-filled when replying when the parent comment has a webmention reference.

## Microformats

Microformats are extensions to HTML for marking up people, organizations, events, locations, blog posts, products,
reviews, resumes, recipes etc. Sites use microformats to publish a standard API that is consumed and used by search
engines, aggregators, and other tools. See https://indieweb.org/microformats for more info. You will want to enable this
if you want to syndicate. Also read https://brid.gy/about#microformats for details how Bridgy decides what to publish if
you are using that service.

Your homepage should contain a h-card entry. This module does not expose this for you (yet). An example:

  ```
  <p class="h-card">My name is <a class="u-url p-name" rel="me" href="/">Your name</a>
  ```

**Classes added for publication (or other functionality)**

- h-entry: added on node or comment wrapper
  see indieweb_preprocess_node() / indieweb_preprocess_comment().
- h-event: added on node wrapper for an event, see indieweb_preprocess_node().
- dt-published, u-url and p-name in node or comment metadata
  see indieweb_preprocess_node() / indieweb_preprocess_comment().
- e-content: added on default body field, see indieweb_preprocess_field().
- u-photo: added on image styles, indieweb_preprocess_image_style().
- p-summary: see indieweb_preprocess_field().
- u-video: see indieweb_preprocess_file_video() and indieweb_preprocess_file_entity_video().

Several field formatters are also available.

You can configure this at /admin/config/services/indieweb/microformats

## IndieAuth: sign in with your domain name and create accounts or use for access tokens.

IndieAuth is a way to use your own domain name to sign in to websites. It works by linking your website to one or more
authentication providers such as Twitter or Google, then entering your domain name in the login form on websites that
support IndieAuth. Indieauth.com and Indielogin.com is a hosted service that does this for you and the latter also
provides Authentication API. Both are open source so you can also host the service yourself.

The easy way is to add rel="me" links on your homepage which point to your social media accounts and on each of those
services adding a link back to your home page. They can even be hidden.

  ```
  <a href="https://twitter.com/swentel" target="_blank" title="Twitter" rel="me"></a>
  ```

You can also use a PGP key if you don't want to use a third party service. See https://indieauth.com/setup for full
details. This module does not expose any of these links or help you with the PGP setup, you will have to manage this
yourself.

If you use apps like Quill (https://quill.p3k.io - web) or Indigenous (Beta iOS, Alpha Android) or other clients which
can post via micropub or read via microsub, the easiest way to let those clients log you in with your domain is by using
indieauth.com too and exchange access tokens for further requests. Only expose these header links if you want to use
micropub or microsub.

## Micropub

Allow posting to your site. Before you can post, you need to authenticate and enable the IndieAuth Authentication API.
Every request will contain an access token which will be verified to make sure it is really you who is posting. See
IndieAuth to configure. More information about micropub: https://indieweb.org/Micropub

A very good client to test is https://quill.p3k.io. A full list is available at https://indieweb.org/Micropub/Clients.
Indigenous (for iOS and Android) are also microsub readers.

Even if you do not decide to use the micropub endpoint, the configuration screen gives you a good overview what kind of
content types and fields you can create which can be used for sending webmentions or read by microformat parsers.

A media endpoint is also available where you can upload files, currently limited to images.

**Supported post types**

- Article: a blog post
- Note: a small post, think of it as a tweet
- Reply: reply on a URL
- Repost: repost a URL
- Like: like a URL
- Bookmark: bookmark a URL
- Event: create an event
- RSVP: create an rsvp
- Issue: create an issue on a repo

Updating existing content is currently limited to change the published status, title and body of nodes and comments.
You can also query for a list of posts. More functionality will be added when this part of the spec matures.
Deleting a node, comment or webmention is possible too.

You can configure this at /admin/config/services/indieweb/micropub

## Creating comments on nodes

When a webmention is saved and is of property 'in-reply-to', it is possible to create a comment if the target of the
webmention has comments enabled.

You have to create an entity reference field on your comment type which points to a webmention. On the 'Manage display'
page of the comment you can set the formatter of that reference field to 'Webmention'. The webmention preprocess 
formats the text content using the 'restricted_html' content format which comes default in Drupal 8. Also, don't
forget to set permissions to view webmentions. When replying, and the comment has a link field, this field can also
be pre-filled, see the 'Sending' section.

Every comment is available also at comment/indieweb/cid so this URL can also be a target for a webmention. If a
webmention is send to this target, a comment will be created on the node, with the target cid as the parent.

Configuration is at /admin/config/services/indieweb/comments

Configuration still in settings.php

  - Match author names with uid's in settings.php. (optional)

  ```
  $settings['indieweb_comment_authors'] = ['Your name' => 3];
  ```

## Feeds

Besides the standard RSS feed which you can create where readers can subscribe to, you can also create microformat2
feeds. These can either return HTML, Atom feed or jf2feed+json (which is experimental though). Atom feeds are generated
using https://granary.io/. You will need feeds when:

- you use Bridgy: the service will look for html link headers with rel="feed" and use those pages to crawl so it knows
  to which content it needs to send webmentions to.
- you want to allow IndieWeb readers (Monocle, Together, Indigenous) to subscribe to your content. These are alternate
  types which can either link to a page with microformat entries. It's advised to have an h-card on that page too as
  some parsers don't go to the homepage to fetch that content.

Because content can be nodes, comments, etc. it isn't possible to use views. However, this module allows you to create a
multiple feeds which aggregates all these content in a page and/or feed. The feeds are controlled by the
'access content' permission.

If your homepage is a feed with microformats, you don't necessarily need this.

Configuration is at /admin/config/services/indieweb/feeds

For more information see

- https://indieweb.org/feed
- https://indieweb.org/jf2
- https://indieweb.org/Atom

## Microsub

Microsub is an early draft of a spec that provides a standardized way for clients to consume and interact with feeds
collected by a server. Readers are Indigenous (iOS and Android), Monocle and Together and many others to come.
Servers are Aperture, Ekster etc.

For more information see

- https://indieweb.org/Microsub
- https://indieweb.org/Microsub-spec

This modules does not expose itself as a microsub server, it mainly allows you to expose the microsub header link.
Note that you also need feeds to be enabled, see the Feeds section.

**Aperture**

If you use Aperture as your Microsub server, you can send a micropub post to one channel when a webmention is received 
by this site. The canonical example is to label that channel name as "Notifications" so you can view incoming 
webmentions on readers like Monocle or Indigenous. Following webmentions are send: likes, reposts, bookmarks, mentions 
and replies.

## Fediverse via Bridgy Fed

Bridgy Fed lets you interact with federated social networks like Mastodon and Hubzilla from your IndieWeb site. It
translates replies, likes, and reposts from webmentions to federated social networking protocols like ActivityPub and
OStatus, and vice versa. Bridgy Fed is open source so you can also host the service yourself. See https://fed.brid.gy/

Currently supports Mastodon, with more coming. You don't need any account at all on any of the social networks.

Just add 'Fediverse|https://fed.brid.gy/' as a syndication target and add the field on the manage display pages of
content types or comments where needed. Currently replies, likes and repost work fine.

- Check https://fed.brid.gy/#setup for additional setup for .htaccess.
- If you use a microsub server, you can subscribe to fediverse users through the microformats feed.

This module exposes a Rabbit hole behavior plugin to return '410 Gone' response. This is useful when you want to delete
a post on the fediverse via Bridgy Fed. The entity itself still needs to exist on the site.  
Install https://www.drupal.org/project/rabbit_hole and the option will be available globally or per entity.

For more background, see https://github.com/snarfed/bridgy-fed/issues/30 and https://indieweb.org/deleted

## Drush commands

- indieweb-send-webmentions (isw): handles the queue for sending webmentions
- indieweb-external-auth-map: maps an existing user account with a domain.

## Hooks

Several hooks are available, see indieweb.api.php

## Want to help out ?

Great! Check the issue queue at https://github.com/swentel/indieweb/issues
