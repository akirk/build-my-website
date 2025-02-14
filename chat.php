<html>
<head>
<?php wp_head(); ?>

</head>
<body>

<h1>Let's build a website!</h1>
<ul id="chat-area">
</ul>
<textarea id="user-input" placeholder="Type your message here..."></textarea>
<button id="send-message">Send</button>
<div id="debug-div">
	<div id="current-request"></div>
	<button id="restart">Start from scratch</button>
</div>

<script>
const chatArea = document.getElementById('chat-area');
const userInput = document.getElementById('user-input');
const sendMessageButton = document.getElementById('send-message');
const debugDiv = document.getElementById('debug-div');
const restartButton = document.getElementById('restart');
const currentRequestDiv = document.getElementById('current-request');

let apiKey = "<?php echo esc_attr( get_option('OPENAI_API_KEY') ); ?>";
if ( ! apiKey ) {
	apiKey = prompt("Please enter your OpenAI API key:");
	fetch('/wp-admin/admin-ajax.php?action=set_openai_key', {
		method: 'POST',
		body: new URLSearchParams({
			openai_key: apiKey
		})
	});
}

const fileUploads = [];
<?php
foreach (get_posts(['post_type' => 'attachment', 'posts_per_page' => -1]) as $attachment) {
	echo "fileUploads.push(";
	$file_contents = 'image';
	if ( 'text/plain' === $attachment->post_mime_type ) {
		$file_contents = file_get_contents(get_attached_file($attachment->ID));
	} else {
		$file_contents = 'image';
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
let messages;
try {
messages = JSON.parse( localStorage.getItem( 'build-messages') || '[]' ) || [];
} catch (e) {
	messages = [];
}
/**
 * drawdown.js
 * (c) Adam Leggett
 */
;function dragdown(src) {

    var rx_lt = /</g;
    var rx_gt = />/g;
    var rx_space = /\t|\r|\uf8ff/g;
    var rx_escape = /\\([\\\|`*_{}\[\]()#+\-~])/g;
    var rx_hr = /^([*\-=_] *){3,}$/gm;
    var rx_blockquote = /\n *&gt; *([^]*?)(?=(\n|$){2})/g;
    var rx_list = /\n( *)(?:[*\-+]|((\d+)|([a-z])|[A-Z])[.)]) +([^]*?)(?=(\n|$){2})/g;
    var rx_listjoin = /<\/(ol|ul)>\n\n<\1>/g;
    var rx_highlight = /(^|[^A-Za-z\d\\])(([*_])|(~)|(\^)|(--)|(\+\+)|`)(\2?)([^<]*?)\2\8(?!\2)(?=\W|_|$)/g;
    var rx_code = /\n((```|~~~).*\n?([^]*?)\n?\2|((    .*?\n)+))/g;
    var rx_link = /((!?)\[(.*?)\]\((.*?)( ".*")?\)|\\([\\`*_{}\[\]()#+\-.!~]))/g;
    var rx_table = /\n(( *\|.*?\| *\n)+)/g;
    var rx_thead = /^.*\n( *\|( *\:?-+\:?-+\:? *\|)* *\n|)/;
    var rx_row = /.*\n/g;
    var rx_cell = /\||(.*?[^\\])\|/g;
    var rx_heading = /(?=^|>|\n)([>\s]*?)(#{1,6}) (.*?)( #*)? *(?=\n|$)/g;
    var rx_para = /(?=^|>|\n)\s*\n+([^<]+?)\n+\s*(?=\n|<|$)/g;
    var rx_stash = /-\d+\uf8ff/g;

    function replace(rex, fn) {
        src = src.replace(rex, fn);
    }

    function element(tag, content) {
        return '<' + tag + '>' + content + '</' + tag + '>';
    }

    function blockquote(src) {
        return src.replace(rx_blockquote, function(all, content) {
            return element('blockquote', blockquote(highlight(content.replace(/^ *&gt; */gm, ''))));
        });
    }

    function list(src) {
        return src.replace(rx_list, function(all, ind, ol, num, low, content) {
            var entry = element('li', highlight(content.split(
                RegExp('\n ?' + ind + '(?:(?:\\d+|[a-zA-Z])[.)]|[*\\-+]) +', 'g')).map(list).join('</li><li>')));

            return '\n' + (ol
                ? '<ol start="' + (num
                    ? ol + '">'
                    : parseInt(ol,36) - 9 + '" style="list-style-type:' + (low ? 'low' : 'upp') + 'er-alpha">') + entry + '</ol>'
                : element('ul', entry));
        });
    }

    function highlight(src) {
        return src.replace(rx_highlight, function(all, _, p1, emp, sub, sup, small, big, p2, content) {
            return _ + element(
                  emp ? (p2 ? 'strong' : 'em')
                : sub ? (p2 ? 's' : 'sub')
                : sup ? 'sup'
                : small ? 'small'
                : big ? 'big'
                : 'code',
                highlight(content));
        });
    }

    function unesc(str) {
        return str.replace(rx_escape, '$1');
    }

    var stash = [];
    var si = 0;

    src = '\n' + src + '\n';

    replace(rx_lt, '&lt;');
    replace(rx_gt, '&gt;');
    replace(rx_space, '  ');

    // blockquote
    src = blockquote(src);

    // horizontal rule
    replace(rx_hr, '<hr/>');

    // list
    src = list(src);
    replace(rx_listjoin, '');

    // code
    replace(rx_code, function(all, p1, p2, p3, p4) {
        stash[--si] = element('pre', element('code', p3||p4.replace(/^    /gm, '')));
        return si + '\uf8ff';
    });

    // link or image
    replace(rx_link, function(all, p1, p2, p3, p4, p5, p6) {
        stash[--si] = p4
            ? p2
                ? '<img src="' + p4 + '" alt="' + p3 + '"/>'
                : '<a href="' + p4 + '">' + unesc(highlight(p3)) + '</a>'
            : p6;
        return si + '\uf8ff';
    });

    // table
    replace(rx_table, function(all, table) {
        var sep = table.match(rx_thead)[1];
        return '\n' + element('table',
            table.replace(rx_row, function(row, ri) {
                return row == sep ? '' : element('tr', row.replace(rx_cell, function(all, cell, ci) {
                    return ci ? element(sep && !ri ? 'th' : 'td', unesc(highlight(cell || ''))) : ''
                }))
            })
        )
    });

    // heading
    replace(rx_heading, function(all, _, p1, p2) { return _ + element('h' + p1.length, unesc(highlight(p2))) });

    // paragraph
    replace(rx_para, function(all, content) { return element('p', unesc(highlight(content))) });

    // stash
    replace(rx_stash, function(all) { return stash[parseInt(all)] });

    return src.trim();
};

function appendMessage(message, sender, replay = false) {
	if ( ! message ) {
		return;
	}
	const listItem = document.createElement('li');
	switch ( sender ) {
		case 'assistant':
			listItem.innerHTML = dragdown( message );
			listItem.className = 'assistant';
			break;
		case 'user':
			listItem.textContent = 'You: ' + message;
			listItem.className = 'user';
			break;
		case 'notice':
			listItem.textContent = message;
			listItem.className = 'notice';

			break;
	}
	chatArea.appendChild(listItem);
	setTimeout(function(){document.body.scrollTop = document.body.scrollHeight;},20);
	if ( sender === 'notice' || replay) {
		return;
	}
	messages.push({ role: sender, content: message });
	localStorage.setItem( 'build-messages', JSON.stringify( messages ) );
	if ( sender === 'user') {
		fetchOpenAIResponse();
	}
}

if ( messages.length > 1 ) {
	while ( messages.length > 0 && ( messages[messages.length - 1].tool_calls || messages[messages.length - 1].role === 'tool' ) ) {
		messages.pop();
	}
	messages.forEach(message => {
		appendMessage(message.content, message.role, true);
	});
	if ( messages[messages.length - 1].role === 'user' ) {
		fetchOpenAIResponse();
	}

} else {
	messages.push({
		role: 'system',
		content: "You are an AI that helps a user to build their website. For this you need to ask them about the topic of their website and gather all necessary information, such as which pages should be created. Please also inquire all meta information that is needed such as name, and potentially address. If the user doesn't give it to you after you ask them, please come back to it later so that you have it before the conversation is ended. The user has the option to drag documents and images to this window, so please in the course of the conversation prompt them to do that. You're not involved with choosing a domain or address for the site but for linking you can just use a relative URL to this domain's root. Please use Gutenberg blocks and use their html for creating the pages but dont show the html to the user just create their pages.",
	} );
	// Start conversation
	appendMessage( "Hi there! I've been tasked to help you with creating a website. Could you tell me a little bit about you and what you'd like to achieve with this website?", 'assistant' );
}
userInput.addEventListener('keyup', function(event) {
	if (event.key === 'Enter') {
		sendMessageButton.click();
	}
});

restartButton.addEventListener('click', function() {
	localStorage.removeItem('build-messages');
	location.reload();
});

document.addEventListener('click', function( e ) {
	const li = e.target.closest('li');
	if ( li && e.shiftKey ) {
		const index = Array.from( li.parentNode.children ).indexOf( e.target );
		messages.splice( index, 1 );
		localStorage.setItem( 'build-messages', JSON.stringify( messages ) );
		li.remove();
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
const dropArea = document.body;

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
		reader.onload = function( file ) {
			const contents = file.target.result;
			fileUploads.push({
				name: files[i].name,
				type: files[i].type,
				contents
			});
		};
		if ( files[i].type === 'text/plain' ) {
			reader.readAsText(files[i]);
		} else {
			fileUploads.push({
				name: files[i].name,
				type: files[i].type,
				contents: 'unknown'
			});
		}
	}

	fetch('/wp-admin/admin-ajax.php?action=upload_files', {
		method: 'POST',
		body: formData,
	})
	.then(response => response.json())
	.then(data => {
		if (data.success) {
			const message = 'I dragged the following files to this chat: ' + Array.from(files).map(file => file.name).join(', ');
			data.data.forEach( file => {
				const attachment = fileUploads.find( f => f.name === file.name );
				if ( attachment ) {
					attachment.id = file.id;
					attachment.url = file.url;
				}
			});

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

async function processResponse( data ) {
	let args, response, ajaxdata;

	if (data.choices && data.choices.length > 0) {
		currentRequestDiv.textContent = (new Date).toLocaleTimeString() + ' Received: ' + data.choices[0].message.content || 'Tool call requests';


		if ( data.choices[0].message.tool_calls ) {
			messages.push(data.choices[0].message);
			for ( let i = 0; i < data.choices[0].message.tool_calls.length; i ++) {
				const toolCall = data.choices[0].message.tool_calls[i];
				switch (toolCall.function.name) {
				case 'list_attachments':
					messages.push({
						role: 'tool',
						tool_call_id: toolCall.id,
						content: JSON.stringify( fileUploads ),
					});
					return fetchOpenAIResponse();
				case 'get_attachment':
					args = JSON.parse(toolCall.function.arguments);
					const attachment = fileUploads.find(file => file.name === args.filename);

					if (attachment) {
						messages.push({
							role: 'tool',
							tool_call_id: toolCall.id,
							content: JSON.stringify(attachment),
						});
					} else {
						messages.push({
							role: 'tool',
							tool_call_id: toolCall.id,
							content: 'Attachment not found',
						});
					}
					break;
				case 'get_available_themes_with_thumbnails':
					messages.push({
						role: 'tool',
						tool_call_id: toolCall.id,
						content: JSON.stringify(<?php
							$themes = array();
							foreach ( wp_get_themes() as $slug => $theme ) {
								$theme_screenshot = $theme->get_screenshot();
								$theme_name = $theme->get( 'Name' );
								if ( empty( $theme->get_block_patterns() ) || ! $theme_screenshot || false !== strpos( $theme_name, 'WordPress.org' ) ) {
									continue;
								}
								$themes[] = [
									'slug' => $slug,
									'name' => $theme_name,
									'screenshot' => $theme_screenshot,
								];
							}
							echo json_encode($themes);
							?>),
					});
					break;
				case 'get_available_gutenberg_patterns':
					args = JSON.parse(toolCall.function.arguments);
					response = await fetch('/wp-admin/admin-ajax.php?action=get_gutenberg_patterns&theme=' + args.theme);
					ajaxdata = await response.json();
					messages.push({
						role: 'tool',
						tool_call_id: toolCall.id,
						content: JSON.stringify(ajaxdata.data),
					});
					break;
				case 'get_gutenberg_pattern_html':
					args = JSON.parse(toolCall.function.arguments);
					response = await fetch('/wp-admin/admin-ajax.php?action=get_gutenberg_pattern_html&slug=' + args.slug + '&theme=' + args.theme);
					ajaxdata = await response.json();
					messages.push({
						role: 'tool',
						tool_call_id: toolCall.id,
						content: JSON.stringify(ajaxdata.data),
					});
					break;
				case 'wp_insert_post':
				case 'wp_update_post':
					args = JSON.parse(toolCall.function.arguments);
					let formData = new FormData();
					for ( let j in args ) {
						formData.append(j, args[j])
					}
					response = await fetch(
						'/wp-admin/admin-ajax.php?action=wp_insert_post',
					 {
							method: 'POST',
							body: formData
						});
					ajaxdata = await response.json();
					messages.push({
						role: 'tool',
						tool_call_id: toolCall.id,
						content: JSON.stringify(ajaxdata.data),
					});
					break;
				}
			}
			return fetchOpenAIResponse();
		} else {
			const aiMessage = data.choices[0].message.content;
			appendMessage(aiMessage, 'assistant');
		}
	} else {
		appendMessage('Sorry, I didn\'t understand that.', 'notice');
	}
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
				name: 'switch_theme',
				description: 'Switch the WordPress site to this theme.',
				parameters:{
					type: "object",
					properties: {
						theme: {
							type: 'string',
							description: 'The theme slug to switch to.'
						}
					},
					required: ['theme']
				}
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
							description: 'The theme slug to get patterns for.'
						}
					},
					required: ['theme']
				}
			}
		},
		{
			type: 'function',
			function: {
				name: 'get_gutenberg_pattern_html',
				description: 'Get the html for an Gutenberg pattern.',
				parameters:{
					type: "object",
					properties: {
						theme: {
							type: 'string',
							description: 'The theme slug.'
						},
						slug: {
							type: 'string',
							description: 'The pattern slug.'
						}
					},
					required: ['theme','slug']
				}
			}
		},
		{
			type: 'function',
			function: {
				name: 'wp_insert_post',
				description: 'Create a WordPress post',
				parameters:{
					type: "object",
					properties: {
						post_title: {
							type: 'string',
							description: 'Post title'
						},
						post_content: {
							type: 'string',
							description: 'Post Content'
						},
						post_type: {
							type: 'string',
							description: 'Post Type'
						}
					},
					required: ['post_title','post_content','post_type']
				}
			},
		},
		{
			type: 'function',
			function: {
				name: 'wp_update_post',
				description: 'Update a WordPress post',
				parameters:{
					type: "object",
					properties: {
						post_id: {
							type: 'string',
							description: 'Post id'
						},
						post_title: {
							type: 'string',
							description: 'Post title'
						},
						post_content: {
							type: 'string',
							description: 'Post Content'
						}
					},
					required: ['post_id','post_title','post_content']

				}
			},
		}
	];
	currentRequestDiv.textContent = (new Date).toLocaleTimeString() + ' Sending: ' + ( messages[messages.length - 1].content || 'Tool call responses ' +messages[messages.length - 1].tool_call_id );


	fetch('https://api.openai.com/v1/chat/completions', {
		method: 'POST',
		headers: {
			'Content-Type': 'application/json',
			'Authorization': `Bearer ${apiKey}`
		},
		body: JSON.stringify({
			model: 'gpt-4o',
			messages,
			tools
		}),
	})
	.then(response => response.json())
	.then(processResponse)
	.catch(error => {
		console.error('Error communicating with OpenAI:', error);
		appendMessage('Error communicating with the AI, please try again later.', 'notice');
	});
}
</script>
<style>
	body {
		font-family: Arial, sans-serif;
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		padding-top: 2em;
		padding-bottom: 4em;
		background-color: #f9f9f9;
		position: relative;
	}

	#chat-area {
		list-style-type: none;
		padding: .5em;
		min-height: 40%;
		border: 1px solid #ccc;
		width: 60%;
		margin-bottom: 10px;
	}

	#chat-area li {
		margin-bottom: .5em;
	}
	#chat-area li strong {
		font-weight: bold;
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
	#chat-area li img {
		max-width: 30%;
	}

	#user-input {
		width: 300px;
		height: 50px;
	}
	#debug-div {
		position: absolute;
		bottom: .5em;
		width: calc(100% - 1em);
		display: flex;
		justify-content: space-between;
	}
	#current-request {
		font-size: 12px;
		height: 1.5em;
		max-width: 80%;
		text-overflow: ellipsis;
		white-space: nowrap;
		overflow: hidden;
	}
	button {
		cursor: pointer;
	}
</style>
<?php wp_footer(); ?>
</html>
