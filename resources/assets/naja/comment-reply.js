/**
 * "Reply" under a blog comment (the data-comment-reply attribute, e.g. "#2 Name ") puts the text into the comment
 * form, which links the comment when it is shown. One listener on the document, so comments added by a snippet need
 * no initialization; without the script the link only goes to the form.
 */
export default class CommentReply {
	initialize() {
		document.addEventListener('click', (event) => {
			const link = event.target.closest('[data-comment-reply]');
			const textarea = document.querySelector('#comment-form textarea');
			if (!link || !textarea) {
				return;
			}

			event.preventDefault();
			const value = textarea.value.trimEnd();
			textarea.value = (value ? value + '\n' : '') + link.dataset.commentReply;
			textarea.scrollIntoView({ block: 'center', behavior: 'smooth' });
			textarea.focus({ preventScroll: true });
			textarea.setSelectionRange(textarea.value.length, textarea.value.length);
		});
	}
}
