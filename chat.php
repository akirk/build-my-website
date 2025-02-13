<!DOCTYPE html>
<html>
	<head><title></title></head>
<body id="website-container">
	<h1>Let's build a website!</h1>
	<ul id="chat-area">
	</ul>
	<textarea id="user-input" placeholder="Type your message here..."></textarea>
	<button id="send-message">Send</button>
<script>
// JavaScript for handling chat functionality and OpenAI API interaction
const chatArea = document.getElementById('chat-area');
const userInput = document.getElementById('user-input');
const sendMessageButton = document.getElementById('send-message');
let apiKey = "<?php echo esc_attr( get_option('OPENAI_API_KEY') ); ?>";
if ( ! apiKey ) {
	apiKey = prompt("Please enter your OpenAI API key:");
}

const fileUploads = [];
<?php
// get all wordpress attachments
foreach (get_posts(['post_type' => 'attachment', 'posts_per_page' => -1]) as $attachment) {
	echo "fileUploads.push(";
	$file_contents = 'image';
	if ( 'text/plain' === $attachment->post_mime_type ) {
		$file_contents = file_get_contents(get_attached_file($attachment->ID));
	}
	echo json_encode([
		'id' => $attachment->ID,
		'name' => $attachment->post_title,
		'type' => $attachment->post_mime_type,
		'contents' => $file_contents,
	]);
	echo ");";

}
?>
const messages = [
// 	{
// 		role: 'system',
// 		content: "You are an AI that helps a user to build their website. For this you need to ask them about the topic of their website and gather all necessary information, such as which pages should be created. Please also inquire all meta information that is needed such as name, and potentially address. If the user doesn't give it to you after you ask them, please come back to it later so that you have it before the conversation is ended. The user has the option to drag documents and images to this window, so please in the course of the conversation prompt them to do that. You're not involved with choosing a domain or address for the site.",
// 	}
// ];
// [
  {
    "role": "system",
    "content": "You are an AI that helps a user to build their website. For this you need to ask them about the topic of their website and gather all necessary information, such as which pages should be created. Please also inquire all meta information that is needed such as name, and potentially address. If the user doesn't give it to you after you ask them, please come back to it later so that you have it before the conversation is ended. The user has the option to drag documents and images to this window, so please in the course of the conversation prompt them to do that. You're not involved with choosing a domain or address for the site."
  },
  {
    "role": "assistant",
    "content": "Hi there! I've been tasked to help you with creating a website. Could you tell me a little bit about you and what you'd like to achieve with this website?"
  },
  {
    "role": "user",
    "content": "im a tennis player\n"
  },
  {
    "role": "assistant",
    "content": "That's great to hear! Are you looking to create a website to showcase your skills, promote your services, share news and updates, or something else related to your tennis career?"
  },
  {
    "role": "user",
    "content": "yes\n"
  },
  {
    "role": "assistant",
    "content": "That's awesome! To get started, could you let me know what specific pages or sections you would like to have on your website? For example, do you want a homepage, an about me section, a services or coaching section, a gallery for your photos or videos, a contact page, or any other specific pages in mind?"
  },
  {
    "role": "user",
    "content": "no sounds good\n"
  },
  {
    "role": "assistant",
    "content": "Great! Let's outline the basic pages for your tennis website:\n\n1. Homepage: A welcoming page that introduces visitors to your website.\n2. About Me: A section where you can share information about yourself, your tennis journey, achievements, and goals.\n3. Services/Coaching: Details about the services you offer, such as tennis coaching, training sessions, etc.\n4. Gallery: A place to showcase photos and videos related to your tennis career.\n5. Contact: Information on how visitors can get in touch with you.\n\nDo these pages align with what you have in mind for your website?"
  },
  {
    "role": "user",
    "content": "yes\n"
  },
  {
    "role": "assistant",
    "content": "Awesome! In addition to the pages, we also need some basic information for your website. Could you please provide me with the following details:\n\n1. Website Name: Do you have a name in mind for your website?\n2. Your Name: The name you would like to display on the website.\n3. Contact Information: This could include an email address or any other contact details you want to share.\n4. Address (optional): If you want to display a physical address on your website, please provide it.\n\nOnce you provide this information, we can move forward with creating your tennis website."
  },
  {
    "role": "user",
    "content": "alex kirk\n"
  },
  {
    "role": "assistant",
    "content": "Thank you for providing your name, Alex Kirk. Could you please let me know if you have a specific name in mind for your website or if you'd like to use your name as the website's title? Additionally, if you have an email address or any other contact information you'd like to include on the website, please share that as well."
  },
  {
    "role": "user",
    "content": "just my name\n"
  },
  {
    "role": "assistant",
    "content": "Great choice! We'll use your name, \"Alex Kirk,\" as the website title. If you have any specific email address or contact information you'd like to share on the website, please provide that information. \n\nOnce we have the necessary details, we can proceed with creating your tennis website."
  },
  {
    "role": "user",
    "content": "i dont want to give my email address or contact information just yet\n"
  },
  {
    "role": "assistant",
    "content": "No problem at all, Alex. We can proceed with creating your tennis website without including your contact information for now. \n\nI will start setting up the basic structure of your website with the pages we discussed. Let's begin by choosing a theme for your website. Would you like to see some options for website themes before we finalize the design?"
  }
];

function appendMessage(message, sender, replay = false) {

	const listItem = document.createElement('li');
	switch ( sender ) {
		case 'assistant':
			listItem.textContent = 'AI: ' + message;
			listItem.className = 'assistant';
			break;
		case 'user':
			listItem.textContent = 'User: ' + message;
			listItem.className = 'user';
			break;
		case 'notice':
			listItem.textContent = message;
			listItem.className = 'notice';

			break;
	}
	chatArea.appendChild(listItem);
	chatArea.scrollTop = chatArea.scrollHeight;
	if ( sender === 'notice' || replay) {
		return;
	}
	messages.push({ role: sender, content: message });

	if ( sender === 'user') {
		fetchOpenAIResponse();
	}
}


if ( messages.length > 1 ) {
	messages.forEach(message => {
		appendMessage(message.content, message.role, true);
	});
} else {
	// Start conversation
	appendMessage("Hi there! I've been tasked to help you with creating a website. Could you tell me a little bit about you and what you'd like to achieve with this website?", 'assistant');
}
userInput.addEventListener('keyup', function(event) {
	if (event.key === 'Enter') {
		sendMessageButton.click();
	}
});

sendMessageButton.addEventListener('click', function() {
	const userMessage = userInput.value;
	if (userMessage.trim() !== '') {
		appendMessage(userMessage, 'user');
		userInput.value = ''; // Clear input
	}
});
userInput.focus();

// Handle file drop
const dropArea = document.getElementById('website-container');

dropArea.addEventListener('dragover', function(event) {
	event.preventDefault(); // Prevent default to allow drop
	event.stopPropagation();
});

dropArea.addEventListener('drop', function(event) {
	event.preventDefault();
	event.stopPropagation();

	const files = event.dataTransfer.files;
	if (files.length > 0) {
		handleFileUpload(files);
	}
});

function handleFileUpload(files) {
	const formData = new FormData();
	for (let i = 0; i < files.length; i++) {
		formData.append('files[]', files[i]);
		const reader = new FileReader();
			reader.onload = (function(file) {
				return function(e) {
					// Display the file content
					resultDiv.innerHTML += `<p><strong>${file.name}:</strong> ${e.target.result}</p>`;
				};
			})(file);

			let contents = reader.readAsText(file); // Read file as text
			// If the files are images, you can use readAsDataURL
			if (file.type.match('image.*')) {
				contents = reader.readAsDataURL(file);
			}

		fileUploads.push({
			name: files[i].name,
			type: files[i].type,
			contents
		});

	}

	fetch('wp-admin/admin-ajax.php?action=upload_files', {
		method: 'POST',
		body: formData,
	})
	.then(response => response.json())
	.then(data => {
		if (data.success) {
			const message = 'I dragged the following files to this chat: ' + Array.from(files).map(file => file.name).join(', ');
			appendMessage('Files uploaded successfully!', 'notice');
			appendMessage(message, 'user');
		} else {
			appendMessage('Error uploading files: ' + data.message, 'notice');
		}
	})
	.catch(error => {
		console.error('Error uploading files:', error);
		appendMessage('Error uploading files, please try again later.', 'notice');
	});
}

function fetchOpenAIResponse() {
	const tools = [
		{
			type: 'function',
			function: {
				name: 'list_attachments',
				description: 'List all attachments that have been uploaded during the conversation.',
			}
		},
		{
			type: 'function',
			function: {
				name: 'get_attachment',
				description: 'Get the contents of a specific attachment.',
				parameters: {
					type: "object",
					properties: {
						filename: {
							type: 'string',
							description: 'The filename of the attachment to retrieve.'
						}
					},
					required: ['filename']
				}
			}
		},
		{
			type: 'function',
			function: {
				name: 'get_available_themes_with_thumbnails',
				description: 'Get a list of available themes with thumbnails.'
			}
		},
		{
			type: 'function',
			function: {
				name: 'get_available_gutenberg_patterns',
				description: 'Get a list of available Gutenberg patterns.',
				parameters:{
					type: "object",
					properties: {
						theme: {
							type: 'string',
							description: 'The theme to get patterns for.'
						}
					},
					required: ['theme']
				}
			}
		}
	];
	fetch('https://api.openai.com/v1/chat/completions', {
		method: 'POST',
		headers: {
			'Content-Type': 'application/json',
			'Authorization': `Bearer ${apiKey}`
		},
		body: JSON.stringify({
			model: 'gpt-3.5-turbo',
			messages,
			tools
		}),
	})
	.then(response => response.json())
	.then(data => {
		if (data.choices && data.choices.length > 0) {
			if ( data.choices[0].message.tool_calls ) {
				data.choices[0].message.tool_calls.forEach(toolCall => {
					switch (toolCall.function.name) {
						case 'list_attachments':
							messages.push({
								role: 'tool',
								tool_call_id: toolCall.id,
								content: fileUploads,
							});
							return fetchOpenAIResponse();
						case 'get_attachment':
							const filename = toolCall.arguments.filename;
							const attachment = fileUploads.find(file => file.name === filename);

							if (attachment) {
								messages.push({
									role: 'tool',
									tool_call_id: toolCall.id,
									content: attachment,
								});
							} else {
								messages.push({
									role: 'tool',
									tool_call_id: toolCall.id,
									content: 'Attachment not found',
								});
							}
							return fetchOpenAIResponse();
						case 'get_available_themes_with_thumbnails':
							messages.push({
								role: 'tool',
								tool_call_id: toolCall.id,
								content: <?php
								$themes = array();
								foreach ( wp_get_themes() as $theme ) {
									$theme_data = $theme->get( 'Name' );
									$theme_screenshot = $theme->get_screenshot();
									$themes[] = [
										'name' => $theme_data,
										'screenshot' => $theme_screenshot,
									];
								}
								echo json_encode($themes);
								?>,
							});
							return fetchOpenAIResponse();
						case 'get_available_gutenberg_patterns':
							fetch('wp-admin/admin-ajax.php?action=get_gutenberg_patterns&theme=' + toolCall.arguments.theme).then(response => response.json()).then(data => {
								messages.push({
									role: 'tool',
									tool_call_id: toolCall.id,
									content: data,
								});
								return fetchOpenAIResponse();
							});
							break;
					}
				});
			}


			const aiMessage = data.choices[0].message.content;
			appendMessage(aiMessage, 'assistant');
		} else {
			appendMessage('Sorry, I didn\'t understand that.', 'notice');
		}
	})
	.catch(error => {
		console.error('Error communicating with OpenAI:', error);
		appendMessage('Error communicating with the AI, please try again later.', 'notice');
	});
}
</script>
<style>
	body {
		font-family: Arial, sans-serif;

	}
	#website-container {
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		height: 100vh;
		background-color: #f9f9f9;
		position: relative;
	}

	#chat-area {
		list-style-type: none;
		padding: .5em;
		height: 40%;
		overflow-y: auto;
		border: 1px solid #ccc;
		width: 60%;
		margin-bottom: 10px;
	}

	#chat-area li {
		margin-bottom: .5em;
	}
	#chat-area li.assistant {
		color: #333;
	}
	#chat-area li.user {
		color: #0074a2;
	}
	#chat-area li.notice {
		color: #999;
	}


	#user-input {
		width: 300px;
		height: 50px;
	}

	button {
		cursor: pointer;
	}
</style>
</body>
</html>
