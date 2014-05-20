L.ready(function() {
	L.live(document.body, ".edit-question", "click", function() {
		var n = this.parentNode.parentNode;
		n.nextSibling.style.display = 'block';
		n.style.display = 'none';
		if (L.hasClass(n, "answer")) n.previousSibling.style.display = 'none';

		var a = L.find(n.nextSibling, "textarea", "answer");
		if (!a) a = L.find(n.nextSibling, "textarea");
		a.focus();

		return false;
	});

	L.live(document.body, ".cancel-question-edit", "click", function() {
		var n = this.parentNode;
		n.previousSibling.style.display = 'block';
		if (L.hasClass(n.previousSibling, "answer")) n.previousSibling.previousSibling.style.display = 'block';
		n.style.display = 'none';
		return false;
	});

	L.live(document.body, ".delete-question", "click", function() {
		if (confirm("Really delete the question?")) {
			L.find(this.parentNode, "input", "mode").value = "delete";
			return true;
		}

		return false;
	});
});
