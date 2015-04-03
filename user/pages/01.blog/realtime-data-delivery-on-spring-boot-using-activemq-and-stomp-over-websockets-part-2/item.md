---
title: Real-Time Data Delivery on Spring Boot Using ActiveMQ and STOMP over WebSockets - Part 2
date: 21:41 03/24/2015 
taxonomy:
    category: blog
    tag: [java, spring boot, activemq, websocket, javascript]
---

In the [previous article](/blog/realtime-data-delivery-on-spring-boot-using-activemq-and-stomp-over-websockets-part-1 "Real-Time Data Delivery on Spring Boot Using ActiveMQ and STOMP over WebSockets - Part 1") we made a very simple app that sent updates to clients in real-time, and in this article, we'll build on that example and make a slightly more complicated app; a private messaging app.

In the first example messages were broadcast to all client, but this time we'll authenticate users with ```Spring Security``` and target them specifically by using STOMP's user-specific methods. We'll also use a message queue (namely, ```Apache ActiveMQ```, which **must be installed on your computer**) to temporarily store messages before sending them out. And as for the transport between the queue and our message dispatchers, we'll use ```Apache Camel```.

>>>>>> For a downloadable version of this code visit the Github repository at [https://github.com/ygunayer/realtime-messaging](https://github.com/ygunayer/realtime-messaging)

So, let's begin by adding the new dependencies to our build script:

```gradle
compile("org.springframework:spring-jms:4.1.5.RELEASE")
compile("org.apache.camel:camel-spring-boot:2.15.0")
compile("org.apache.activemq:activemq-camel:5.10.0")
```

And then, configuration. For simplicity's sake we'll configure an in-memory authentication with three built-in users. The users are as follows:

| Username | Password |
| ------ | ------ |
| user1 | pass1 |
| user2 | pass2 |
| user3 | pass3 |

And here's the configuration.

```java
@Configuration
@EnableWebSecurity
public class SecurityConfig extends WebSecurityConfigurerAdapter {

    @Autowired
    public void configureGlobal(AuthenticationManagerBuilder auth) throws Exception {
        // configure a simple in-memory authentication with three users
        auth.inMemoryAuthentication()
            .withUser("user1").password("pass1").roles("USER")
            .and()
            .withUser("user2").password("pass2").roles("USER")
            .and()
            .withUser("user3").password("pass3").roles("USER");
    }

    @Bean
    public SessionRegistry sessionRegistry() {
        return new SessionRegistryImpl();
    }

    @Bean
    public ServletListenerRegistrationBean<HttpSessionEventPublisher> httpSessionEventPublisher() {
        return new ServletListenerRegistrationBean<HttpSessionEventPublisher>(new HttpSessionEventPublisher());
    }

    @Override
    protected void configure(HttpSecurity http) throws Exception {
        super.configure(http);
        // disable CSRF for simplicity and configure a session registry which will allow us to fetch a list of users
        http.csrf().disable().sessionManagement().maximumSessions(-1).sessionRegistry(sessionRegistry());
    }
}
```

Next, we'll tell Apache Camel to listen for changes on a certain queue, and when a new message is inserted, redirect it to our own message dispatcher beans (the Camel route will be as follows: ```apache:activemq -> bean:queueHandler```). One thing to note, however, is that Camel will try to create and use an in-memory ActiveMQ instance. Since we don't want that, we'll have to tell it to use our own ActiveMQ instance, which by default runs on ```tcp://localhost:61616```.

>>>>> If you've just installed ActiveMQ or haven't changed much of its settings, you can visit [http://localhost:8161](http://localhost:8161) and follow the "Manage Broker" link to monitor the status of your queues. When prompted for credentials, use ```admin``` for both username and password.

```java
@Configuration
public class CamelConfig {

    @Bean
    ConnectionFactory jmsConnectionFactory() {
        // use a pool for ActiveMQ connections
        PooledConnectionFactory pool = new PooledConnectionFactory();
        pool.setConnectionFactory(new ActiveMQConnectionFactory("tcp://localhost:61616"));
        return pool;
    }

    @Bean
    RouteBuilder myRouter() {
        return new RouteBuilder() {

            @Override
            public void configure() throws Exception {
                // listen the queue named rt_messages and upon receiving a new entry
                // simply redirect it to a bean named queueHandler which will then send it to users over STOMP
                from("activemq:rt_messages").to("bean:queueHandler");
            }
        };
    }
}
```

Okay! Now let's look at our WebSocketController class which has changed slightly. This time it'll notify all clients when a user joins the topic.

```java
@Controller
public class WebSocketController {

    @Autowired
    private SimpMessagingTemplate messageTemplate;

    /**
     * Listens the /app/messaging endpoint and when a message is received, gets the user information encapsulated within it, and informs all clients
     * listening at the /topic/users endpoint that the user has joined the topic.
     * 
     * @param message the encapsulated STOMP message
     */
    @MessageMapping("/messaging")
    public void messaging(Message<Object> message) {
        // get the user associated with the message
        Principal user = message.getHeaders().get(SimpMessageHeaderAccessor.USER_HEADER, Principal.class);
        // notify all users that a user has joined the topic
        messageTemplate.convertAndSend("/topic/users", user.getName());
    }
}
```

Then, let's modify our MessageDTO class slightly by adding two new fields; ```from``` and ```to```, and move it to its own file so it can be discovered by ActiveMQ while serializing/deserializing.

```java
/**
 * A simple DTO class to encapsulate messages along with their destinations and timestamps.
 */
public class MessageDTO implements Serializable {

    private static final long serialVersionUID = 1L;

    public Date date;
    public String content;
    public String to;
    public String from;

    public MessageDTO() {
        this.date = Calendar.getInstance().getTime();
    }
}
```

Then, the controller. We only need two functions: receive "send message" requests from clients and send them to ActiveMQ, and provide a list of users who are logged-in. To send messages to ActiveMQ we'll use the Camel context instance and to retrieve the list of users we'll use Spring Security's session registry.

```java
@RestController
public class APIController {

    @Autowired
    private CamelContext camelContext;

    @Autowired
    @Qualifier("sessionRegistry")
    private SessionRegistry sessionRegistry;

    /**
     * Receives the messages from clients and sends them to ActiveMQ.
     * 
     * @param message the message to send, encapsulated in a wrapper
     */
    @RequestMapping(value = "/send", method = RequestMethod.POST, consumes = "application/json")
    public void sendMessage(@RequestBody MessageDTO message, Principal currentUser) {
        // send any message sent by clients to a queue called rt_messages
        message.from = currentUser.getName();
        camelContext.createProducerTemplate().sendBody("activemq:rt_messages", message);
    }

    /**
     * Returns the names of the currently logged-in users.
     * 
     * @return set of user names
     */
    @RequestMapping(value = "/users", method = RequestMethod.GET, produces = "application/json")
    public Set<String> getUsers() {
        // get the list of users from Spring Security's session registry
        return sessionRegistry.getAllPrincipals().stream().map(u -> ((User) u).getUsername()).collect(Collectors.toSet());
    }
}
```

Then, the message dispatcher. This is just a named Spring component which will be realized by Camel when a message is added to the queue. The only function of this class is to send a message to its recipient, but if we somehow wanted to process our message in some kind of way (say, sending mails), we could do it here.

```java
/**
 * Receives messages from ActiveMQ and relays them to appropriate users.
 */
@Component(value = "queueHandler")
public class QueueHandler {

    @Autowired
    private SimpMessageSendingOperations msgTemplate;

    private static Map<String, Object> defaultHeaders;

    static {
        defaultHeaders = new HashMap<String, Object>();
        // add the Content-Type: application/json header by default
        defaultHeaders.put(MessageHeaders.CONTENT_TYPE, MimeTypeUtils.APPLICATION_JSON);
    }

    public void handle(Exchange exchange) {
        Message camelMessage = exchange.getIn();
        MessageDTO message = camelMessage.getBody(MessageDTO.class);
        // send the message specifically to the destination user by using STOMP's user-directed messaging
        msgTemplate.convertAndSendToUser(message.to, "/topic/messages", message, defaultHeaders);
    }
}
```

And finally, the client code. This time it's a little bit more complicated, but still no biggie.

```html
<!DOCTYPE html>
<html>
<head>
	<title>Realtime Messaging</title>
	<style>
		.message.outgoing {
			color: blue;
		}
	</style>
</head>
<body>
	<div>
		<h3>Send a Message</h3>
		<p>To:</p> <select id="to"></select>
		<p>Message:</p>
		<textarea id="message"></textarea>
		<button type="button" id="send">Send</button>
	</div>
	<div>
		<h3>Messages Received</h3>
		<ol id="messages"></ol>
	</div>

	<script src="http://code.jquery.com/jquery-1.11.2.min.js"></script>
	<script type="text/javascript" src="sockjs-0.3.4.js"></script>
	<script type="text/javascript" src="stomp.js"></script>

	<script type="text/javascript">
		// encapsulates interaction methods and event bindings for readability
		var UIHelper = function(api) {
			var messageList = $("#messages");
			var sendButton = $("#send");
			var userList = $("#to");
			var messageField = $("#message");
			var self = this;
			
			sendButton.on("click", function(e) {
				var message = self.getCurrentMessage();
				self.appendMessage(message);
				api.sendMessage(message, function() {
					self.setMessageContent("");
				});
			});
			
			var getMessageString = function(message) {				
				// the date parameter is used differently because its format differs on the client on the server
				if(!!message.isIncoming)
					return "<li class=\"message incoming\"><p>&lt;&lt;&lt; Sent by " + message.from + " on " + new Date(message.date) + "</p><div>" + message.content + "</li>";
				else
					return "<li class=\"message outgoing\"><p>&gt;&gt;&gt; Sent to " + message.to + " on " + message.date + "</p><div>" + message.content + "</li>";
			};

			var getUserListEntry = function(username) {
				return "<option value=\"" + username + "\">" + username + "</option>";
			};
			
			this.getCurrentMessage = function() {
				var message = {
					to: userList.val(),
					content: messageField.val(),
               		date: new Date(),
               		isIncoming: false
				};
				return message;
			};
			
			this.appendMessage = function(message) {
				var messageEntry = getMessageString(message);
				messageList.append(messageEntry);
			};
			
			this.appendUsername = function(username) {
				// check if the user is already on the list so as not to add them twice
				if(userList.find("[value='" + username +"']").length > 0)
					return;
				
				var userEntry = getUserListEntry(username);
				userList.append(userEntry);
			};
			
			this.setMessageContent = function(content) {
				messageField.val(content);
			};
		};
		
		// wraps API calls for readability
		var APIClient = function() {
			var defaultHeaders = {
				"Content-Type": "application/json"
			};
			
			this.getLoggedInUsers = function(then) {
				$.ajax({
            		url: "/users",
            		method: "GET",
            		headers: defaultHeaders,
            		dataType: "json",
            		success: function(users) {
            			if(typeof then === "function")
            				then(users);
            		}
            	});
			};
			
			this.sendMessage = function(message, then) {
				$.ajax({
            		url: "/send",
            		method: "POST",
            		headers: defaultHeaders,
            		data: JSON.stringify(message),
                	success: function() {
            			if(typeof then === "function")
            				then();
                	}
            	});
			};
		};
	
		$(document).ready(function() {
			var api = new APIClient();
			var ui = new UIHelper(api);

			var socket = new SockJS('/messaging');
			var stompClient = Stomp.over(socket);
            stompClient.connect({ }, function(frame) {
            	
            	// subscribe to the /topic/messages endpoint which feeds newly added messages
                stompClient.subscribe('/user/topic/messages', function(data) {
                	// when a message is received add it to the end of the list
                    var body = data.body;
                    var message = JSON.parse(body);
                    message.isIncoming = true;
                    message.date = new Date(message.date);
                    ui.appendMessage(message);
                });
            	
            	// subscribe to the /topic/users to get notified when a user subscribes to the server
            	stompClient.subscribe('/topic/users', function(data) {
            		var username = data.body;
            		ui.appendUsername(username);
            	});
            	
            	// get the list of users and populate the select box
            	api.getLoggedInUsers(function(users) {
            		for(var i = 0, l = users.length; i < l; i++)
            			ui.appendUsername(users[i]);
            	});
            	
            	// notify the server
            	stompClient.send("/app/messaging", {}, "");
            });
		});
	</script>
</body>
</html>
```

And here's a demonstration:

<video controls>
	<source src="/videos/messaging/realtime-messaging.webm" type="video/webm" />
	<source src="/videos/messaging/realtime-messaging.mp4" type="video/mp4" />
	Your browser does not support the video tag.
</video>

That's it! Well... almost. With authentication, message persistence and user-specific message dispatching as well as broadcasting, this example has been more realistic, but as always, there's still more to do. While we've persisted messages, there's still a chance that they might get lost because unless specified otherwise, they won't be added back to the queue if an error occurs while they're being processed. The solution is to use a ```Dead Letter Queue``` (DLQ) to store any message that could not be processed, and if you'd like your app to retry processing them, configure a simple Camel route to re-queue messages from the DLQ back to the original queue at intervals and in bulk. Also, the Javascript code written here is pretty brittle because it doesn't take into account the stateful nature of our app, and it's right inside the HTML code to begin with! And as for the authentication I can't even begin.

Hopefully in the future I'll create a better, more in-depth articles to illustrate points that need special care, but until then, stay tuned.