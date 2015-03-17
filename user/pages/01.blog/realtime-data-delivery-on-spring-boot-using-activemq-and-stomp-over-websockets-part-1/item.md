---
title: Real-Time Data Delivery on Spring Boot Using ActiveMQ and STOMP over WebSockets - Part 1
date: 23:41 03/16/2015 
taxonomy:
    category: blog
    tag: [java, spring boot, activemq, websocket, javascript]
---
We live in the age of digitized social interaction. As demonstrated by the countless gameplay video streams of games over 20 years old, we feel the need to socialize with even the most static digital content. Now, everything is collaborative, en masse and in real-time, so web apps need to be able to handle this concurrent and user-scoped data in an elegant fashion in order to survive.

The traditional approach to handing out clients data in real-time (or quasi-real-time) was to make clients poll the server at regular intervals. This could be achieved by a simple polling (say, every 5 seconds), or long-polling (same as the other, but with longer-lasting sockets and late responses), but now there are new kids in town. The new approach to this situation is to let the server ```push``` the data using a publish/subscribe mechanism, rather than the client ```pull```ing them in, and technologies like ```Comet```, ```WebSocket```, ```Server-Sent Events``` were created for this exact purpose.

This article will demonstrate a simple guestbook app that uses the WebSocket method, and will feature [Spring Boot](http://projects.spring.io/spring-boot/) and [STOMP](https://stomp.github.io/) on the server side, and [SockJS](https://github.com/sockjs/sockjs-client) and [stomp.js](http://jmesnil.net/stomp-websocket/doc/) on the client side.

To download the code for the project, visit the Github repository I created for this article: [https://github.com/ygunayer/realtime-guestbook](https://github.com/ygunayer/realtime-guestbook)

First, let me quickly explain what ```STOMP``` is.  As you might already know, WebSocket is a pretty low-level transport mechanism, so in order to do RPC over WebSocket you have to roll your own message format, both for the call and for the result. This might be tricky and time-consuming, especially on the serialization part, and ```STOMP```, which stands for *Streaming Text Oriented Messaging Protocol*, aims to fix this very problem by defining a few commands. The ```spring-messaging``` package provides the necessary classes to implement this protocol, so we'll be using that to simplify our work. If you're interested in more information on STOMP, visit the [STOMP website](https://stomp.github.io/) or the relevant [Wikipedia page](http://en.wikipedia.org/wiki/Streaming_Text_Oriented_Messaging_Protocol).

Now, let's model our demo. We'll want to have a very simple guest book system where users will anonymously enter messages which will then be delivered in real-time to other visitors, but since it's not the objective of this article, these messages will not be persisted. In order to create such an app, we'll first have to create a build script (i.e. ```pom.xml``` or ```build.gradle```) with the following dependencies: ```org.springframework.boot:spring-boot-starter-web```, ```org.springframework.boot:spring-boot-starter-websocket``` and ```org.springframework:spring-messaging```. Here's an example for Gradle:

```gradle
...
dependencies {
    compile("org.springframework.boot:spring-boot-starter-web")
    compile("org.springframework.boot:spring-boot-starter-websocket")
    compile("org.springframework:spring-messaging")
    testCompile("junit:junit")
}
...
```

Then, like with any other Spring Boot project, we'll define an application class to serve as the entry point.

```java
@SpringBootApplication
public class Application {

    public static void main(String[] args) {
        ConfigurableApplicationContext context = SpringApplication.run(Application.class, args);
    }
}
```

Then, we'll configure the WebSocket endpoints.

```java
@Configuration
@EnableWebSocketMessageBroker
public class WebSocketConfig extends AbstractWebSocketMessageBrokerConfigurer {

    @Override
    public void configureMessageBroker(MessageBrokerRegistry config) {
        // use the /topic prefix for outgoing WebSocket communication
        config.enableSimpleBroker("/topic");

        // use the /app prefix for others
        config.setApplicationDestinationPrefixes("/app");
    }

    @Override
    public void registerStompEndpoints(StompEndpointRegistry registry) {
        // use the /guestbook endpoint (prefixed with /app as configured above) for incoming requests
        registry.addEndpoint("/guestbook").withSockJS();
    }
}
```

Then, we'll define a simple controller to accept incoming messages and relays them to listening WebSocket clients using STOMP.

```java
@Controller
public class GuestbookController {

/**
     * A simple DTO class to encapsulate messages along with their timestamps.
     */
    public static class MessageDTO {

        public Date date;
        public String content;

        public MessageDTO(String message) {
            this.date = Calendar.getInstance().getTime();
            this.content = message;
        }
    }

    /**
     * Listens the /app/guestbook endpoint and when a message is received, encapsulates it in a MessageDTO instance and relays the resulting object to
     * the clients listening at the /topic/entries endpoint.
     * 
     * @param message the message
     * @return the encapsulated message
     */
    @MessageMapping("/guestbook")
    @SendTo("/topic/entries")
    public MessageDTO guestbook(String message) {
        System.out.println("Received message: " + message);
        return new MessageDTO(message);
    }
}
```

Finally, we'll create a very simple page to serve as the UI. Notice how the messages are appended when they're received from the WebSocket listener, rather than after they've been sent.

```html
<!DOCTYPE html>
<html>
<head>
	<title>Realtime Guestbook</title>
</head>
<body>
	<div>
		<p>Write a message:</p>
		<textarea id="message"></textarea>
		<button type="button" id="send">Send</button>
	</div>
	<div>
		<h3>Messages:</h3>
		<ol id="messages"></ol>
	</div>

	<script src="http://code.jquery.com/jquery-1.11.2.min.js"></script>
	<script type="text/javascript" src="sockjs-0.3.4.js"></script>
	<script type="text/javascript" src="stomp.js"></script>

	<script type="text/javascript">
		$(document).ready(function() {
			var messageList = $("#messages");

			var getMessageString = function(message) {
				var date = new Date(message.date);
				return "<li><p>Received: " + date + "</p><div>" + message.content + "</li>";
			};

			var socket = new SockJS('/guestbook');
			var stompClient = Stomp.over(socket);
            stompClient.connect({ }, function(frame) {
            	// subscribe to the /topic/entries endpoint which feeds newly added messages
                stompClient.subscribe('/topic/entries', function(data) {
                	// when a message is received add it to the end of the list
                    var body = data.body;
                    var message = JSON.parse(body);
                    messageList.append(getMessageString(message));
                });
            });

            $("#send").on("click", function() {
            	// send the message
            	stompClient.send("/app/guestbook", {}, $("#message").val());
            	$("#message").val("");
            });
		});
	</script>
</body>
</html>
```

And, that's it! Here's a simple demonstration using two different browser windows:

<video controls>
	<source src="/blog/videos/guestbook/guestbook.webm" type="video/webm" />
	<source src="/blog/videos/guestbook/guestbook.mp4" type="video/mp4" />
	Your browser does not support the video tag.
</video>

As you can see, these messages are global so everyone can see them. To protect these messages, we'll be using STOMP's user-scoped message delivery utilities to target specific users in the next article, so please stay tuned!