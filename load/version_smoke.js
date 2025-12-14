import http from "k6/http";
import { check, sleep } from "k6";

export const options = {
    vus: 25,
    duration: "30s",
    thresholds: {
        http_req_failed: ["rate<0.01"],
        http_req_duration: ["p(95)<200"],
    },
};

const BASE_URL = __ENV.BASE_URL || "https://odds.lc";
const SPORT = __ENV.SPORT || "soccer_uefa_europa_conference_league";

let etag = null; // per VU instance

export default function () {
    const headers = {};
    if (etag) headers["If-None-Match"] = etag;

    const res = http.get(`${BASE_URL}/api/lines/version?sport=${encodeURIComponent(SPORT)}`, {
        headers,
        tags: { name: "version" },
    });

    check(res, {
        "status 200/304": (r) => r.status === 200 || r.status === 304,
    });

    if (res.status === 200) {
        etag = res.headers["ETag"] || res.headers["Etag"] || null;
    }

    sleep(0.05);
}
