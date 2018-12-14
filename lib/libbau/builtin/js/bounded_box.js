function textCountdown(field, count, limit) {
	if (field.value.length > limit) {
		field.value = field.value.substring(0, limit);
	}
	else {
		count.value = limit - field.value.length;
	}
}