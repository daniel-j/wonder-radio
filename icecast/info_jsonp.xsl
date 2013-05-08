<!-- http://slug.blog.aeminium.org/2011/03/02/icecast-streams-jsonp/-->
<xsl:stylesheet xmlns:xsl = "http://www.w3.org/1999/XSL/Transform" version = "1.0" >
<xsl:output omit-xml-declaration="yes" method="xml" indent="yes" encoding="UTF-8" />
<xsl:template match = "/icestats" >
{
    <xsl:for-each select="source">&quot;<xsl:value-of select="@mount" />&quot; : {
        "name": &quot;<xsl:value-of select="server_name" />&quot;,
        "description": &quot;<xsl:value-of select="server_description" />&quot;,
        "listeners": <xsl:value-of select="listeners" />,
        "listeners_peak": <xsl:value-of select="listener_peak" />,
        "artist": &quot;<xsl:value-of select="artist" />&quot;,
        "title": &quot;<xsl:value-of select="title" />&quot;,
        "genre": &quot;<xsl:value-of select="genre" />&quot;,
        "url": &quot;<xsl:value-of select="server_url" />&quot;,
        "started": "<xsl:value-of select="stream_start" />"
    },</xsl:for-each>"":0
}
</xsl:template>
</xsl:stylesheet>