const esc_pattern = sequence => {
    return sequence.replace(/[\-\[\]{}()*+?.,\\$\^|#\s]/g, '\\$&');
};

const parse_pattern = (sequence, advanced) => {
    // Prevent ReDoS: limit input length
    if (sequence.length > 200) {
        return esc_pattern(sequence.substr(0, 200));
    }

    if (!advanced) {
        return esc_pattern(sequence);
    }

    if (sequence.substr(0, 3) === 're:') {
        const re_pattern = sequence.substr(3);
        // Limit regex pattern length to prevent ReDoS
        if (re_pattern.length > 200) {
            return esc_pattern(re_pattern.substr(0, 200));
        }
        return re_pattern;
    }

    return sequence.trim().split(/\s+/).map(part => {
        return part.split('').map(char => esc_pattern(char)).join('.*?');
    }).join('|');
};

module.exports = {
    parsePattern: parse_pattern
};
