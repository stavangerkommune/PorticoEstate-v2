import {fallbackLng} from "@/app/i18n/settings";
import {i18n} from "i18next";

/**
 * A dictionary of named HTML entities and their corresponding character representations.
 */
const htmlEntities: { [key: string]: string } = {
    nbsp: ' ',
    cent: '¢',
    pound: '£',
    yen: '¥',
    euro: '€',
    copy: '©',
    reg: '®',
    lt: '<',
    gt: '>',
    quot: '"',
    amp: '&',
    apos: '\''
};

/**
 * Converts HTML entities in a string to their corresponding characters.
 * Handles both named entities (e.g., &amp;) and numeric entities (e.g., &#x26; or &#38;).
 *
 * @param str - The input string containing HTML entities.
 * @returns The unescaped string with HTML entities replaced by their respective characters.
 */
export function unescapeHTML(str: string): string {
    return str.replace(/&([^;]+);/g, (entity: string, entityCode: string): string => {
        let match: RegExpMatchArray | null;

        // Check if the entity code matches a named entity
        if (entityCode in htmlEntities) {
            return htmlEntities[entityCode];
        }
        // Check for hexadecimal numeric entities (e.g., &#x26;)
        else if ((match = entityCode.match(/^#x([\da-fA-F]+)$/))) {
            return String.fromCharCode(parseInt(match[1], 16));
        }
        // Check for decimal numeric entities (e.g., &#38;)
        else if ((match = entityCode.match(/^#(\d+)$/))) {
            return String.fromCharCode(parseInt(match[1], 10));
        }
        // If no match, return the entity as-is
        else {
            return entity;
        }
    });
}


export function extractDescriptionText(description_json: string, i18n: i18n): string | null {
    const descriptionJson = JSON.parse(description_json);
    // @ts-ignore
    let description = descriptionJson[i18n.language];
    if (!description) {
        // @ts-ignore
        description = descriptionJson[fallbackLng.key];
    }
    if (!description) {
        return null;
    }
    return description;
}
