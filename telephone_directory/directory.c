
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <ctype.h>

#define MAXBUF 8192
#define DBFILE "C:\\xampp\\cgi-bin\\phonebook.csv"


static void url_decode_inplace(char *src) {
    char *dst = src;
    while (*src) {
        if (*src == '+') { *dst++ = ' '; src++; }
        else if (*src == '%' && isxdigit((unsigned char)src[1]) && isxdigit((unsigned char)src[2])) {
            int v1 = (src[1] > '9' ? (toupper(src[1])-'A'+10) : (src[1] - '0'));
            int v2 = (src[2] > '9' ? (toupper(src[2])-'A'+10) : (src[2] - '0'));
            *dst++ = (char)(v1*16 + v2);
            src += 3;
        } else { *dst++ = *src++; }
    }
    *dst = '\0';
}

static char* dup_range(const char* start, const char* end) {
    size_t len = (size_t)(end - start);
    char *out = (char*)malloc(len + 1);
    if (!out) return NULL;
    memcpy(out, start, len);
    out[len] = '\0';
    return out;
}


static char* get_param(const char *data, const char *key) {
    if (!data || !key) return NULL;
    size_t klen = strlen(key);
    const char *p = data;
    while (p && *p) {
        
        const char *found = strstr(p, key);
        if (!found) return NULL;
        
        if ((found == data || *(found-1) == '&') && found[klen] == '=') {
            const char *v = found + klen + 1;
            const char *end = strchr(v, '&');
            if (!end) end = v + strlen(v);
            char *val = dup_range(v, end);
            if (!val) return NULL;
            url_decode_inplace(val);
            return val;
        }
        p = found + 1;
    }
    return NULL;
}

static char* json_escape(const char *s) {
    if (!s) s = "";
    size_t len = strlen(s);
    /* worst case escape every char -> 2x plus */
    char *out = (char*)malloc(len * 2 + 3);
    char *d = out;
    for (const char *p = s; *p; ++p) {
        unsigned char c = (unsigned char)*p;
        switch (c) {
            case '\\': *d++ = '\\'; *d++ = '\\'; break;
            case '"': *d++ = '\\'; *d++ = '"'; break;
            case '\n': *d++ = '\\'; *d++ = 'n'; break;
            case '\r': *d++ = '\\'; *d++ = 'r'; break;
            case '\t': *d++ = '\\'; *d++ = 't'; break;
            default:
                if (c < 0x20) { /* control */
                    sprintf(d, "\\u%04x", c);
                    d += 6;
                } else {
                    *d++ = c;
                }
        }
    }
    *d = '\0';
    return out;
}

static int icontains(const char *hay, const char *needle) {
    if (!needle || !*needle) return 1;
    size_t hl = strlen(hay), nl = strlen(needle);
    char *H = (char*)malloc(hl+1), *N = (char*)malloc(nl+1);
    if (!H || !N) { if (H) free(H); if (N) free(N); return 0; }
    for (size_t i=0;i<hl;++i) H[i] = (char)tolower((unsigned char)hay[i]); H[hl]='\0';
    for (size_t i=0;i<nl;++i) N[i] = (char)tolower((unsigned char)needle[i]); N[nl]='\0';
    char *pos = strstr(H, N);
    free(H); free(N);
    return pos != NULL;
}

/* ---------- Storage ---------- */
static int append_entry(const char *name, const char *phone, const char *email) {
    FILE *f = fopen(DBFILE, "a");
    if (!f) return -1;
    fprintf(f, "%s,%s,%s\n", name ? name: "", phone?phone:"", email?email:"");
    fclose(f);
    return 0;
}

static void list_entries_html(const char *filter) {
    FILE *f = fopen(DBFILE, "r");
    if (!f) { printf("<p>No entries found.</p>\n"); return; }
    char line[MAXBUF];
    printf("<table><tr><th>Name</th><th>Phone</th><th>Email</th></tr>\n");
    while (fgets(line, sizeof(line), f)) {
        /* split csv: name,phone,email (no commas inside fields) */
        char *name = strtok(line, ",\r\n");
        char *phone = strtok(NULL, ",\r\n");
        char *email = strtok(NULL, ",\r\n");
        char buf[MAXBUF];
        snprintf(buf, sizeof(buf), "%s %s %s", name?name:"", phone?phone:"", email?email:"");
        if (!filter || icontains(buf, filter)) {
            printf("<tr><td>%s</td><td>%s</td><td>%s</td></tr>\n",
                   name?name:"", phone?phone:"", email?email:"");
        }
    }
    printf("</table>\n");
    fclose(f);
}

static void list_entries_json(const char *filter) {
    FILE *f = fopen(DBFILE, "r");
    printf("Content-Type: application/json\r\n\r\n");
    if (!f) { printf("[]"); return; }
    char line[MAXBUF];
    int first = 1;
    printf("[");
    while (fgets(line, sizeof(line), f)) {
        char *name = strtok(line, ",\r\n");
        char *phone = strtok(NULL, ",\r\n");
        char *email = strtok(NULL, ",\r\n");
        char buf[MAXBUF];
        snprintf(buf, sizeof(buf), "%s %s %s", name?name:"", phone?phone:"", email?email:"");
        if (filter && !icontains(buf, filter)) continue;
        char *jn = json_escape(name?name:"");
        char *jp = json_escape(phone?phone:"");
        char *je = json_escape(email?email:"");
        if (!first) printf(","); first = 0;
        printf("{\"name\":\"%s\",\"phone\":\"%s\",\"email\":\"%s\"}", jn, jp, je);
        free(jn); free(jp); free(je);
    }
    printf("]");
    fclose(f);
}

/* ---------- HTML wrappers ---------- */
static void html_head(const char *title) {
    printf("Content-Type: text/html; charset=utf-8\r\n\r\n");
    printf("<!doctype html><html lang=\"en\"><head><meta charset=\"utf-8\">\n");
    printf("<meta name=\"viewport\" content=\"width=device-width,initial-scale=1\">\n");
    printf("<title>%s</title>\n", title);
    printf("<style>body{font-family:Arial;padding:18px}table{border-collapse:collapse;width:100%%}th,td{border:1px solid #ddd;padding:8px}th{background:#f4f4f4}</style>\n");
    printf("</head><body>\n");
    printf("<h1>%s</h1>\n", title);
}

static void html_foot(void) {
    printf("<hr><p><a href=\"/telephone_directory/index.html\">Back to Home</a></p>\n");
    printf("</body></html>\n");
}

int main(void) {
    /* read body or query into postbuf */
    char *method = getenv("REQUEST_METHOD");
    if (!method) method = "GET";
    char buf[MAXBUF] = {0};
    if (strcmp(method, "POST") == 0) {
        char *lenstr = getenv("CONTENT_LENGTH");
        if (lenstr) { int len = atoi(lenstr); if (len > 0 && len < MAXBUF) { int r = fread(buf, 1, len, stdin); buf[r] = '\0'; } }
    } else {
        char *qs = getenv("QUERY_STRING");
        if (qs) strncpy(buf, qs, sizeof(buf)-1);
    }

    char *action = get_param(buf, "action");

    /* JSON API endpoints for SPA */
    if (action && strcmp(action, "json_list") == 0) {
        char *q = get_param(buf, "q");
        list_entries_json(q);
        if (q) free(q);
        if (action) free(action);
        return 0;
    }
    if (action && strcmp(action, "json_add") == 0) {
        char *name = get_param(buf, "name");
        char *phone = get_param(buf, "phone");
        char *email = get_param(buf, "email");
        printf("Content-Type: application/json\r\n\r\n");
        if (!name || !*name || !phone || !*phone) {
            /* missing required fields */
            printf("{\"ok\":false,\"error\":\"missing name or phone\"}");
        } else {
            int rc = append_entry(name, phone, email);
            if (rc == 0) {
                printf("{\"ok\":true}");
            } else {
                printf("{\"ok\":false,\"error\":\"cannot write database\"}");
            }
        }
        if (name) free(name); if (phone) free(phone); if (email) free(email);
        if (action) free(action);
        return 0;
    }

    /* HTML fallback for direct CGI access */
    html_head("All Contacts");

    if (action && strcmp(action, "add") == 0) {
        char *name = get_param(buf, "name");
        char *phone = get_param(buf, "phone");
        char *email = get_param(buf, "email");
        if (name && *name && phone && *phone) {
            if (append_entry(name, phone, email) == 0)
                printf("<p style=\"color:green\">Entry added successfully.</p>\n");
            else
                printf("<p style=\"color:red\">Error writing database.</p>\n");
        } else {
            printf("<p style=\"color:red\">Name and phone are required.</p>\n");
        }
        if (name) free(name); if (phone) free(phone); if (email) free(email);
    }

    if (action && strcmp(action, "search") == 0) {
        char *q = get_param(buf, "q");
        list_entries_html(q);
        if (q) free(q);
    } else {
        list_entries_html(NULL);
    }

    html_foot();
    if (action) free(action);
    return 0;
}
