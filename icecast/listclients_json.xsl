<xsl:stylesheet xmlns:xsl = "http://www.w3.org/1999/XSL/Transform" version = "1.0" >
<xsl:output omit-xml-declaration="yes" method="html" indent="yes" encoding="UTF-8" />
<xsl:template match = "/icestats" ><xsl:for-each select="source">{
	"<xsl:value-of select="@mount" />": [
		<xsl:for-each select="listener">{
			"ip": "<xsl:value-of select="IP" />",
			"time": <xsl:value-of select="Connected" />
		},</xsl:for-each>0
	],
	"": 0
}</xsl:for-each>
</xsl:template>
</xsl:stylesheet>
